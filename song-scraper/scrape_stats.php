<?php

//Config

include ('config.php');

if (php_sapi_name() == "cli") {
    // In cli-mode
} else {
	// Not in cli-mode
	if (!isset($_GET['security_key']) || $_GET['security_key'] != $security_key){die("Fuck off");}
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);   
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

//look for any Stats.xml files in the profile directory
foreach (glob("{$profileDir}/*/Stats.xml",GLOB_BRACE) as $xml_file){

	if (!file_exists($xml_file)){
		exit ("Stats.xml file not found!");
	}

	//open xml file
	$xml = simplexml_load_file($xml_file);

	// Example xml structure of Stats.xml file:
	// $xml->SongScores->Song[11]['Dir'];
	// $xml->SongScores->Song[11]->Steps['Difficulty'];
	// $xml->SongScores->Song[11]->Steps['StepsType'];
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Grade;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Score;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->PercentDP;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Modifiers;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->DateTime;

	foreach ($xml->SongScores->Song as $song){
		
		$songScores = array();
		$song_dir = "";
		$song_id = "";
		$song_title = "";
		$song_pack = "";
		$steps_type = "";
		$difficulty = "";
		$username = "";
		$num_played = "";
		$last_played = "";

		
		$song_dir = $song['Dir'];
		//find the folder name and set as the pack name
		$song_pack = substr($song_dir, 0, strripos($song_dir, "/"));
		$song_pack = substr($song_pack, 0, strripos($song_pack, "/"));
		$song_pack = substr($song_pack, strripos($song_pack, "/")+1);
		//use the folder name as the song title
		$song_title = substr($song_dir, 0, strripos($song_dir, "/"));
		$song_title = substr($song_title, strripos($song_title, "/")+1);
		
		foreach ($song->Steps as $steps){		
			$steps_type = $steps['StepsType']; //dance-single, dance-double, etc.
			$difficulty = $steps['Difficulty']; //Beginner, Medium, Expert, etc.
			
			foreach ($steps->HighScoreList as $high_score_lists){
				$num_played = $high_score_lists->NumTimesPlayed; //useful for getting popular songs
				$last_played = $high_score_lists->LastPlayed; //date the song/difficulty was last played
				
				//check if the number of times played has increased and update db
				$sql0 = "SELECT song_dir, MAX(numplayed) AS numplayed, MAX(lastplayed) AS lastplayed FROM sm_scores WHERE numplayed < '{$num_played}' AND lastplayed <= '{$last_played}' AND difficulty = \"{$difficulty}\" AND stepstype = \"{$steps_type}\"";
				$retval = mysqli_query($conn, $sql0);
				if (mysqli_num_rows($retval) > 0){
					//there are updates, update all db records for song_dir
					$sql0 = "UPDATE sm_scores SET numplayed = \"{$num_played}\", lastplayed = \"{$last_played}\" WHERE song_dir = \"{$song_dir}\" AND difficulty = \"{$difficulty}\" AND stepstype = \"{$steps_type}\"";
					if (!$retval = mysqli_query($conn, $sql0)){
						echo "Error: " . $sql0 . "\n" . mysqli_error($conn) . "\n";
					}
				}
				
				foreach ($high_score_lists->HighScore as $high_score){
					$songScores = array();
					$songScores = array($high_score_lists['HighScore']=>$high_score);
					$songScores = array_shift($songScores);
					
					//print_r($songScores);
					
					//look for existing record and skip if found
					$sql1 = "SELECT * FROM sm_scores WHERE song_dir=\"{$song_dir}\" AND stepstype=\"{$steps_type}\" AND difficulty=\"{$difficulty}\" AND score=\"{$songScores->Score}\" AND datetime=\"{$songScores->DateTime}\"";
					$retval = mysqli_query($conn, $sql1);
						
					if (mysqli_num_rows($retval) == 0){
						//this record is not in the db, let's put that beautiful score in there!
						//but first, lets grab the song id from the songlist db
						$sql_id = "SELECT id, song_dir, title, pack FROM sm_songs WHERE song_dir=\"{$song_dir}\" ORDER BY id ASC";
						$id_result = mysqli_query( $conn, $sql_id );
						if(mysqli_num_rows($id_result) == 1){
							$song_id = mysqli_fetch_assoc($id_result)["id"];
						}elseif(mysqli_num_rows($id_result) > 1){
							$song_ids = implode(", ",$id_result)["id"];
							$song_id = 0;
							echo "Multiple possible IDs found for {$song_title} in {$song_pack}: {$song_ids}\n";
						}elseif(mysqli_num_rows($id_result) == 0){
							$song_id = 0;
							echo "No song ID found for {$song_title} in {$song_pack}. Moving on...\n";
						}
						
						//Let's build the VALUES string!
						$sql1_values = "(\"{$song_dir}\",\"{$song_id}\",\"{$song_title}\",\"{$song_pack}\",\"{$difficulty}\",\"{$steps_type}\",\"{$num_played}\",\"{$last_played}\",\"{$songScores->Name}\",\"{$songScores->Grade}\",\"{$songScores->Score}\",\"{$songScores->PercentDP}\",\"{$songScores->Modifiers}\",\"{$songScores->DateTime}\",\"{$songScores->SurviveSeconds}\",\"{$songScores->LifeRemainingSeconds}\",\"{$songScores->Disqualified}\",\"{$songScores->MaxCombo}\",\"{$songScores->StageAward}\",\"{$songScores->PeakComboAward}\",\"{$songScores->PlayerGuid}\",\"{$songScores->MachineGuid}\",\"{$songScores->TapNoteScores->HitMine}\",\"{$songScores->TapNoteScores->AvoidMine}\",\"{$songScores->TapNoteScores->CheckpointMiss}\",\"{$songScores->TapNoteScores->Miss}\",\"{$songScores->TapNoteScores->W5}\",\"{$songScores->TapNoteScores->W4}\",\"{$songScores->TapNoteScores->W3}\",\"{$songScores->TapNoteScores->W2}\",\"{$songScores->TapNoteScores->W1}\",\"{$songScores->TapNoteScores->CheckpointHit}\",\"{$songScores->HoldNoteScores->LetGo}\",\"{$songScores->HoldNoteScores->Held}\",\"{$songScores->HoldNoteScores->MissedHold}\",\"{$songScores->RadarValues->Stream}\",\"{$songScores->RadarValues->Voltage}\",\"{$songScores->RadarValues->Air}\",\"{$songScores->RadarValues->Freeze}\",\"{$songScores->RadarValues->Chaos}\",\"{$songScores->RadarValues->Notes}\",\"{$songScores->RadarValues->TapsAndHolds}\",\"{$songScores->RadarValues->Jumps}\",\"{$songScores->RadarValues->Holds}\",\"{$songScores->RadarValues->Mines}\",\"{$songScores->RadarValues->Hands}\",\"{$songScores->RadarValues->Rolls}\",\"{$songScores->RadarValues->Lifts}\",\"{$songScores->RadarValues->Fakes}\")"; 
						
						//print_r($sql1_values);
							
						echo "Adding a " . $songScores->Grade . " grade for the " . $difficulty . " chart of " . $song_title . " from " . $song_pack . " \n";
						
						$sql2 = "INSERT INTO sm_scores (song_dir,song_id,title,pack,difficulty,stepstype,numplayed,lastplayed,username,grade,score,percentdp,modifiers,datetime,survive_seconds,life_remaining_seconds,disqualified,max_combo,stage_award,peak_combo_award,player_guid,machine_guid,hit_mine,avoid_mine,checkpoint_miss,miss,w5,w4,w3,w2,w1,checkpoint_hit,let_go,held,missed_hold,stream,voltage,air,freeze,chaos,notes,taps_holds,jumps,holds,mines,hands,rolls,lifts,fakes) VALUES {$sql1_values}";
						if (!mysqli_query($conn, $sql2)){
							echo "Error: " . $sql2 . "\n" . mysqli_error($conn) . "\n";
						}
					}else{
						//echo "This entry already exists in the db, skipping \n";
					}
				}
			}
		}
	}
}
//send songid to sm_requests to mark request as completed

	$sql3 = "UPDATE sm_requests
			JOIN sm_scores ON sm_scores.song_id=sm_requests.song_id
			SET state = 'completed'
			WHERE sm_requests.state = 'requested' AND sm_scores.datetime > sm_requests.request_time 
			ORDER BY datetime DESC
			LIMIT 1";
	if (!$retval = mysqli_query($conn, $sql3)){
		echo "Error: " . $sql3 . "\n" . mysqli_error($conn) . "\n";
		}else{
			if(!mysqli_affected_rows($conn)===false){
				echo "Marking request as complete.";
			}else{
				$sql3 = "UPDATE sm_requests
				JOIN sm_scores ON sm_scores.song_id=sm_requests.song_id
				SET state = 'completed'
				WHERE sm_requests.state = 'requested' AND sm_scores.lastplayed >= DATE(sm_requests.request_time) AND sm_scores.lastplayed <= DATE_ADD(sm_requests.request_time, INTERVAL 6 HOUR) 
				ORDER BY lastplayed DESC
				LIMIT 1";
				if (!$retval = mysqli_query($conn, $sql3)){
					echo "Error: " . $sql3 . "\n" . mysqli_error($conn) . "\n";
				}elseif(!mysqli_affected_rows($conn)===false){
					echo "Marking request as complete (fallback).";
				}
			}
		}

mysqli_close($conn);

?>