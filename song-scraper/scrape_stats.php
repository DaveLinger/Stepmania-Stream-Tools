<?php

/////
//SM5 Stats.xml scraper
//Call this scraper each time the Stats.xml file(s) are modified.
/////

//Config

include ('config.php');

function find_statsxml($directory){
	//look for any Stats.xml files in the profile directory
	$file_arr = array();
	foreach (glob($directory."/*/Stats.xml",GLOB_BRACE) as $xml_file){
		$file_arr[] = $xml_file;
	}
		if (empty($file_arr)){
			exit ("Stats.xml file not found!");
		}
	return $file_arr;
}

function statsXMLtoArray ($xml_file){
	//create array to store xml file
	$statsLastPlayed = array();
	$statsHighScores = array();
	$stats_arr = array();
	
	//open xml file
	$xml = simplexml_load_file(utf8_encode($xml_file));

	// Example xml structure of Stats.xml file:
	// $xml->SongScores->Song[11]['Dir'];
	// $xml->SongScores->Song[11]->Steps['Difficulty'];
	// $xml->SongScores->Song[11]->Steps['StepsType'];
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Grade;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Score;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->PercentDP;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->Modifiers;
	// $xml->SongScores->Song[11]->Steps->HighScoreList->HighScore->DateTime;

	$display_name = (string)$xml->GeneralData->DisplayName;

	foreach ($xml->SongScores->Song as $song){
		$song_dir = (string)$song['Dir'];
		
		foreach ($song->Steps as $steps){		
			$steps_type = (string)$steps['StepsType']; //dance-single, dance-double, etc.
			$difficulty = (string)$steps['Difficulty']; //Beginner, Medium, Expert, etc.
			
			foreach ($steps->HighScoreList as $high_score_lists){
				$num_played = (string)$high_score_lists->NumTimesPlayed; //useful for getting popular songs
				$last_played = (string)$high_score_lists->LastPlayed; //date the song/difficulty was last played

				$dateTimeHS = array(null);
				$highScores = array();

				foreach ($high_score_lists->HighScore as $high_score){				
					$highScores[] = $high_score;
					$dateTimeHS[] = (string)$high_score->DateTime;
				}

				$dateTimeMax = max($dateTimeHS);
				if (strtotime($dateTimeMax) > strtotime($last_played)){
					$last_played = $dateTimeMax;
				}
				
				if (!empty($highScores)){
					foreach ($highScores as $highScoreSingle){
						$statsHighScores[] = array('DisplayName' => $display_name, 'SongDir' => $song_dir, 'StepsType' => $steps_type, 'Difficulty' => $difficulty, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played, 'HighScore' => $highScoreSingle);
					}
				}

				$statsLastPlayed[] = array('DisplayName' => $display_name, 'SongDir' => $song_dir, 'StepsType' => $steps_type, 'Difficulty' => $difficulty, 'NumTimesPlayed' => $num_played, 'LastPlayed' => $last_played);
	
			}
		}
	}

	$stats_arr = array('LastPlayed' => $statsLastPlayed, 'HighScores' => $statsHighScores);
	return $stats_arr; 
}

function curlPost($postSource, $array){
	global $target_url_status;
	global $security_key;
	//add the security_key to the array
	$jsonArray = array('security_key' => $security_key, 'source' => $postSource, 'data' => $array);
	//encode array as json
	$post = json_encode($jsonArray);
	//this curl method only works with PHP 5.5+
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$target_url_status);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //this being false is probaby bad?
	curl_setopt($ch, CURLOPT_POST,1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$result = curl_exec ($ch);
	$error = curl_strerror(curl_errno($ch));
	curl_close ($ch);
	echo $result; //echo from the server-side script

	return $error;
}

$file_arr = find_statsxml ($profileDir);
foreach ($file_arr as $file){
	$stats_arr = statsXMLtoArray ($file);
	//LastPlayed
	curlPost("lastplayed", $stats_arr['LastPlayed']);
	//HighScores
	curlPost("highscores", $stats_arr['HighScores']);
	echo "Done \n";

}

?>