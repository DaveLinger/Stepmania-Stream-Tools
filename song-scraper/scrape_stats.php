<?php

//database config
$dbhost = '';
$dbuser = '';
$dbpass = '';
$db = '';

//location of stats.xml file
$xml_file = 'C:/Users/[USERNAME]/AppData/Roaming/StepMania 5.1/Save/LocalProfiles/00000000/Stats.xml';

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

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $db);   
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}


foreach ($xml->SongScores->Song as $song){
	
	$song_dir = "";
	$song_id = "";
	$song_title = "";
	$song_pack = "";
	$steps_type = "";
	$difficulty = "";
	$username = "";
	$num_played = "";
	$grade = "";
	$score = "";
	$percentdp = "";
	$modifiers = "";
	$datetime = "";
	
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
			
			foreach ($high_score_lists->HighScore as $high_score){
				$username = $high_score->Name; //profile name of this high score
				$grade = $high_score->Grade; //grade tier according to the theme running at the time of record
				$score = $high_score->Score; //dance points score
				$percentdp = $high_score->PercentDP; //straight percent score
				$modifiers = $high_score->Modifiers; //string of all modifiers (noteskin, speedmods, etc.)
				$datetime = $high_score->DateTime; //when the record was completed
						
			if($grade != 'Failed'){
				//look for existing record and skip if found
				$sql1 = "SELECT * FROM sm_scores WHERE song_dir=\"{$song_dir}\" AND stepstype=\"{$steps_type}\" AND difficulty=\"{$difficulty}\" AND score=\"{$score}\" AND datetime=\"{$datetime}\"";
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
							
						echo "Adding a " . $grade . " grade for the " . $difficulty . " chart of " . $song_title . " from " . $song_pack . " \n";
						
						$sql2 = "INSERT INTO sm_scores (song_dir, song_id, title, pack, difficulty, stepstype, numplayed, username, grade, score, percentdp, modifiers, datetime) values (\"{$song_dir}\", \"{$song_id}\", \"{$song_title}\", \"{$song_pack}\", \"{$difficulty}\", \"{$steps_type}\", \"{$num_played}\", \"{$username}\", \"{$grade}\", \"{$score}\", \"{$percentdp}\", \"{$modifiers}\", \"{$datetime}\")";
						if (!mysqli_query($conn, $sql2)){
							echo "Error: " . $sql2 . "\n" . mysqli_error($conn) . "\n";
						}
					}else{
						//echo "This entry already exists in the db, skipping \n";
					}
				}else{
					//echo "Not adding a 'Failed' grade. That's dumb. \n";
				}
			}
		}
	}
}

mysqli_close($conn);

?>
