<?php
//--------Status.php--------//
//This file is responsible for processing the converted Stats.xml json array.
//Up to three tasks are accomplished at each run depending on the "source" value of the json string. The json string should look something like this:
//json: {EXAMPLE HERE}
//////////////////////////
//Tasks:
//1. Process the lastplayed data adding or updating entries for each unique song (songDir) in the sm_songsplayed table. The lastplayed data from the Stats.xml files can be in two formats: (1) with a full timestamp, if a new highscore was achieved, or (2) with only a date stamp, if no new highscore was achieved. The addLastPlayedtoDB function attempts to add/update any new values. This table is used to keep a record of when and how many times a song is played, which is critical for any of the random request commands. 
//2. Process any new highscores (also from Stats.xml files) an populate the sm_scores table. This information opens huge opertunities for score tracking stream widgets, score-based chat commands, etc.
//3. Determine if a recently completed song was requested beforehand and mark the request as "complete" in the sm_requests table.
//////////////////
//Possible future function supported to offload the song scraping from the client machine to the webserver.

//--------Configuration--------//

include ("config.php");

//--------Accept the POSTed json string, validate, and check security--------//
	
//Make sure that it is a POST request.
if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') != 0){
    echo('Request method must be POST!');
}
 
//Make sure that the content type of the POST request has been set to application/json
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if(strcasecmp($contentType, 'application/json') != 0){
    echo('Content type must be: application/json');
}
 
//Receive the RAW post data.
$content = trim(file_get_contents("php://input"));
 
//Attempt to decode the incoming RAW post data from JSON.
$jsonDecoded = json_decode($content, true);
 
//If json_decode failed, the JSON is invalid.
if(!is_array($jsonDecoded)){
    echo('Received content contained invalid JSON!');
}

if (!isset($jsonDecoded['security_key']) || $jsonDecoded['security_key'] != $security_key || !isset($jsonDecoded['source']) || empty($jsonDecoded['data'])){die("Fuck off");}

//--------Open mysql link--------//

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);   
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function splitSongDir($song_dir){
	//This function splits the "song_dir" string into title and pack
	
	$splitDir = array();
	//find the folder name and set as the pack name
	$song_pack = substr($song_dir, 0, strripos($song_dir, "/"));
	$song_pack = substr($song_pack, 0, strripos($song_pack, "/"));
	$song_pack = substr($song_pack, strripos($song_pack, "/")+1);
	//use the folder name as the song title
	$song_title = substr($song_dir, 0, strripos($song_dir, "/"));
	$song_title = substr($song_title, strripos($song_title, "/")+1);
	//return array containing the title and pack
	$splitDir = array ('title' => $song_title, 'pack' => $song_pack);
	return $splitDir;
}

function lookupSongID ($song_dir){
	//This function looks up the song ID that matches the song_dir in the sm_songs db
	global $conn;
	$songInfo = array();
	//query for IDs matching the song_dir
	$sql_id = "SELECT id, title, pack FROM sm_songs WHERE song_dir=\"{$song_dir}\" ORDER BY id ASC";
	$id_result = mysqli_query($conn, $sql_id);
	if(mysqli_num_rows($id_result) == 1){
		//1 result found - set array from query results
		$songInfo = mysqli_fetch_assoc($id_result);
	}elseif(mysqli_num_rows($id_result) > 1){
		//more than 1 result found - set array from split song_dir, but set id=0
		$songInfo = mysqli_fetch_assoc($id_result);
		$song_ids = implode(", ",$id_result)['id'];
		$song_id = "0";
		$song_title = splitSongDir($song_dir)['title'];
		$song_pack = splitSongDir($song_dir)['pack'];
		$songInfo = array('id' => $song_id, 'title' => $song_title, 'pack' => $song_pack);
		//notify user that there are duplicate results
		echo "Multiple possible IDs found for {$song_title} in {$song_pack}: {$song_ids}\n";
	}elseif(mysqli_num_rows($id_result) == 0){
		//no results found - set array from split song_dir and id=0
		$song_id = "0";
		$song_title = splitSongDir($song_dir)['title'];
		$song_pack = splitSongDir($song_dir)['pack'];
		$songInfo = array('id' => $song_id, 'title' => $song_title, 'pack' => $song_pack);
		//notify user that an ID was not found in the sm_songs db
		echo "No song ID found for {$song_title} in {$song_pack}. Moving on...\n";
	}
	return $songInfo;
}

function addLastPlayedtoDB ($lastplayed_array){
	//This function inserts or updates song records in the sm_songsplayed db 
	global $conn;
	$lastplayedIDUpdated = array();

	//$sqlLastDate = "SELECT MAX(lastplayed) AS lastplayed FROM sm_songsplayed";
	//$dbLastDate = mysqli_fetch_assoc(mysqli_query($conn, $sqlLastDate)['lastplayed']);

	foreach ($lastplayed_array as $lastplayed){
		//loop through the array and parse the lastplayed information
		//check if this entry exists already
		$sql0 = "SELECT * FROM sm_songsplayed WHERE song_dir = \"{$lastplayed['SongDir']}\" AND numplayed = \"{$lastplayed['NumTimesPlayed']}\" AND lastplayed >= \"{$lastplayed['LastPlayed']}\" AND difficulty = \"{$lastplayed['Difficulty']}\" AND stepstype = \"{$lastplayed['StepsType']}\" AND username = \"{$lastplayed['DisplayName']}\"";
		if (!$retval = mysqli_query($conn, $sql0)){
			echo "Error: " . $sql0 . "\n" . mysqli_error($conn) . "\n";
		}
		if (mysqli_num_rows($retval) == 0){
			//existing record is not found - let's either update or insert a record
			$id = "";
			//check if the number of times played has increased and update db
			$sql0 = "SELECT * FROM sm_songsplayed WHERE song_dir = \"{$lastplayed['SongDir']}\" AND numplayed < \"{$lastplayed['NumTimesPlayed']}\" AND lastplayed <= \"{$lastplayed['LastPlayed']}\" AND difficulty = \"{$lastplayed['Difficulty']}\" AND stepstype = \"{$lastplayed['StepsType']}\" AND username = \"{$lastplayed['DisplayName']}\"";
			if (!$retval = mysqli_query($conn, $sql0)){
				echo "Error: " . $sql0 . "\n" . mysqli_error($conn) . "\n";
			}
			if (mysqli_num_rows($retval) > 0){
				//there are updates - update the db record for song_dir
				$id = mysqli_fetch_assoc($retval)['id'];
				$sql0 = "UPDATE sm_songsplayed SET numplayed = \"{$lastplayed['NumTimesPlayed']}\", lastplayed = \"{$lastplayed['LastPlayed']}\", datetime = NOW() WHERE id = \"{$id}\"";
				if (!$retval = mysqli_query($conn, $sql0)){
					echo "Error: " . $sql0 . "\n" . mysqli_error($conn) . "\n";
				}
			}elseif (mysqli_num_rows($retval) == 0){
				//record does not exist - insert a new row
				$songInfo = lookupSongID($lastplayed['SongDir']);
				$song_id = $songInfo['id'];
				$sql0 = "INSERT INTO sm_songsplayed (song_id,song_dir,stepstype,difficulty,username,numplayed,lastplayed,datetime) VALUES (\"{$song_id}\",\"{$lastplayed['SongDir']}\",\"{$lastplayed['StepsType']}\",\"{$lastplayed['Difficulty']}\",\"{$lastplayed['DisplayName']}\",\"{$lastplayed['NumTimesPlayed']}\",\"{$lastplayed['LastPlayed']}\",NOW())";
				if (!$retval = mysqli_query($conn, $sql0)){
					echo "Error: " . $sql0 . "\n" . mysqli_error($conn) . "\n";
				}
				$id = mysqli_insert_id($conn);
			}
			//save row ids of updated/inserted records for marking requests later
			$lastplayedIDUpdated[] = $id;
		}else{
			//echo "record already exists. No need to update/insert.";
		}
	}
	return $lastplayedIDUpdated;
}

function markRequest ($idArray){
	//This function updates the sm_requests table if requests were completed
	global $conn;
	//$ids = implode(",",$idArray);
	
	foreach ($idArray as $id){
		//send songID to sm_requests to mark request as completed
		//first, we check if there is a new fully timestamped update
		$sql3 = "UPDATE sm_requests
		JOIN sm_songsplayed ON sm_songsplayed.song_id=sm_requests.song_id
		SET state = 'completed'
		WHERE sm_requests.state = 'requested' AND sm_songsplayed.id = {$id} AND sm_songsplayed.lastplayed > sm_requests.request_time AND sm_songsplayed.lastplayed > DATE(sm_songsplayed.lastplayed) 
		ORDER BY lastplayed DESC, request_time DESC";
		if (!$retval = mysqli_query($conn, $sql3)){echo "Error: " . $sql3 . "\n" . mysqli_error($conn) . "\n";}
		if (mysqli_affected_rows($conn) > 0){
			echo "Marking request as complete.\n";
		}else{
			//if no fully timestamp update is found, we fallback to determining an update by an increase in NumTimesPlayed
			$sql3 = "UPDATE sm_requests
			JOIN sm_songsplayed ON sm_songsplayed.song_id=sm_requests.song_id
			SET state = 'completed'
			WHERE sm_requests.state = 'requested' AND sm_songsplayed.id = {$id} AND (DATE(sm_songsplayed.lastplayed) = DATE(sm_requests.request_time) OR sm_songsplayed.lastplayed = DATE(sm_songsplayed.lastplayed))  
			ORDER BY lastplayed DESC, request_time DESC";
			if (!$retval = mysqli_query($conn, $sql3)){echo "Error: " . $sql3 . "\n" . mysqli_error($conn) . "\n";}
			if (mysqli_affected_rows($conn) > 0){
				echo "Marking request as complete (fallback).\n";
			}
			//add the time to the lastplayed timestamp, if it's obvious what time it should be
			$sql3 = "SELECT * FROM sm_songsplayed WHERE id = {$id}";
			//echo $sql3."\n";
			$retval3 = mysqli_fetch_assoc(mysqli_query($conn, $sql3));
			$dateTime = strtotime($retval3['datetime']);
			$lastplayedDate = strtotime($retval3['lastplayed']);
			$dateTimeDate = strtotime(date("Y-m-j",$dateTime));
			if ($dateTimeDate == $lastplayedDate){	
				$newDT = date("Y-m-j",$lastplayedDate) . " " . date("H:i:s",$dateTime);
				$sql3 = "UPDATE sm_songsplayed SET lastplayed = \"{$newDT}\" WHERE id = {$id}";
				if (!$retval = mysqli_query($conn, $sql3)){echo "Error: " . $sql3 . "\n" . mysqli_error($conn) . "\n";}
				echo "Updated lastplayed timestamp from ".date("Y-m-j",$lastplayedDate)." to {$newDT}.\n";
			}
			
		}
	}
}

function addHighScoretoDB ($highscore_array){
	//This function adds highscore entries into the sm_scores table
	global $conn;

	foreach ($highscore_array as $highscore){
		//look for existing record and skip if found
		$sql1 = "SELECT * FROM sm_scores 
		WHERE song_dir=\"{$highscore['SongDir']}\" AND stepstype=\"{$highscore['StepsType']}\" AND difficulty=\"{$highscore['Difficulty']}\" AND score=\"{$highscore['HighScore']['Score']}\" AND datetime=\"{$highscore['HighScore']['DateTime']}\" AND username =\"{$highscore['DisplayName']}\"";
		$retval = mysqli_query($conn, $sql1);
			
		if (mysqli_num_rows($retval) == 0){
			//this record is not in the table, let's put that beautiful score in there!
			//but first, lets grab the song id from the songlist db
			$songInfo = lookupSongID($highscore['SongDir']);
			$song_id = $songInfo['id'];
			//clean quotes from song titles and packs
			$song_title = str_ireplace("\"","",$songInfo['title']);
			$song_pack = str_ireplace("\"","",$songInfo['pack']);
			//the StageAward and PeakComboAward fields can sometimes be an array and need to be converted to strings
			if(is_array($highscore['HighScore']['StageAward'])){
				$stageAward = implode(',',$highscore['HighScore']['StageAward']);
			}else{
				$stageAward = $highscore['HighScore']['StageAward'];
			}
			if(is_array($highscore['HighScore']['PeakComboAward'])){
				$peakComboAward = implode(',',$highscore['HighScore']['PeakComboAward']);
			}else{
				$peakComboAward = $highscore['HighScore']['PeakComboAward'];
			}
			
			//Let's build the VALUES string!
			$sql1_values = "(\"{$highscore['SongDir']}\",\"{$song_id}\",\"{$song_title}\",\"{$song_pack}\",\"{$highscore['Difficulty']}\",\"{$highscore['StepsType']}\",\"{$highscore['DisplayName']}\",\"{$highscore['HighScore']['Grade']}\",\"{$highscore['HighScore']['Score']}\",\"{$highscore['HighScore']['PercentDP']}\",\"{$highscore['HighScore']['Modifiers']}\",\"{$highscore['HighScore']['DateTime']}\",\"{$highscore['HighScore']['SurviveSeconds']}\",\"{$highscore['HighScore']['LifeRemainingSeconds']}\",\"{$highscore['HighScore']['Disqualified']}\",\"{$highscore['HighScore']['MaxCombo']}\",\"{$stageAward}\",\"{$peakComboAward}\",\"{$highscore['HighScore']['PlayerGuid']}\",\"{$highscore['HighScore']['MachineGuid']}\",\"{$highscore['HighScore']['TapNoteScores']['HitMine']}\",\"{$highscore['HighScore']['TapNoteScores']['AvoidMine']}\",\"{$highscore['HighScore']['TapNoteScores']['CheckpointMiss']}\",\"{$highscore['HighScore']['TapNoteScores']['Miss']}\",\"{$highscore['HighScore']['TapNoteScores']['W5']}\",\"{$highscore['HighScore']['TapNoteScores']['W4']}\",\"{$highscore['HighScore']['TapNoteScores']['W3']}\",\"{$highscore['HighScore']['TapNoteScores']['W2']}\",\"{$highscore['HighScore']['TapNoteScores']['W1']}\",\"{$highscore['HighScore']['TapNoteScores']['CheckpointHit']}\",\"{$highscore['HighScore']['HoldNoteScores']['LetGo']}\",\"{$highscore['HighScore']['HoldNoteScores']['Held']}\",\"{$highscore['HighScore']['HoldNoteScores']['MissedHold']}\",\"{$highscore['HighScore']['RadarValues']['Stream']}\",\"{$highscore['HighScore']['RadarValues']['Voltage']}\",\"{$highscore['HighScore']['RadarValues']['Air']}\",\"{$highscore['HighScore']['RadarValues']['Freeze']}\",\"{$highscore['HighScore']['RadarValues']['Chaos']}\",\"{$highscore['HighScore']['RadarValues']['Notes']}\",\"{$highscore['HighScore']['RadarValues']['TapsAndHolds']}\",\"{$highscore['HighScore']['RadarValues']['Jumps']}\",\"{$highscore['HighScore']['RadarValues']['Holds']}\",\"{$highscore['HighScore']['RadarValues']['Mines']}\",\"{$highscore['HighScore']['RadarValues']['Hands']}\",\"{$highscore['HighScore']['RadarValues']['Rolls']}\",\"{$highscore['HighScore']['RadarValues']['Lifts']}\",\"{$highscore['HighScore']['RadarValues']['Fakes']}\")"; 
				
			echo "Adding a " . $highscore['HighScore']['Grade'] . " grade for the " . $highscore['Difficulty'] . " chart of " . $song_title . " from " . $song_pack . " \n";
			
			$sql2 = "INSERT INTO sm_scores (song_dir,song_id,title,pack,difficulty,stepstype,username,grade,score,percentdp,modifiers,datetime,survive_seconds,life_remaining_seconds,disqualified,max_combo,stage_award,peak_combo_award,player_guid,machine_guid,hit_mine,avoid_mine,checkpoint_miss,miss,w5,w4,w3,w2,w1,checkpoint_hit,let_go,held,missed_hold,stream,voltage,air,freeze,chaos,notes,taps_holds,jumps,holds,mines,hands,rolls,lifts,fakes) VALUES {$sql1_values}";
			if (!mysqli_query($conn, $sql2)){
				echo "Error: " . $sql2 . "\n" . mysqli_error($conn) . "\n";
			}
		}else{
			//echo "This entry already exists in the db, skipping \n";
		}
	}
}

//--------Process the JSON and run specific functions based on data type--------// 

if ($jsonDecoded['source'] == "songs"){
	//recieve json from songs scraper
}

if ($jsonDecoded['source'] == "lastplayed"){
	//recieve json from stats scraper
	echo "Updating songs played...\n";
	$lastplayedIDUpdated = addLastPlayedtoDB($jsonDecoded['data']);
	if(!empty($lastplayedIDUpdated)){
		echo "Completing song requests...\n";
		markRequest($lastplayedIDUpdated);
	}
}

if ($jsonDecoded['source'] == "highscores"){
	//recieve json from stats scraper
	echo "Adding highscores to DB...\n";
	addHighScoretoDB($jsonDecoded['data']);
}

mysqli_close($conn);

?>