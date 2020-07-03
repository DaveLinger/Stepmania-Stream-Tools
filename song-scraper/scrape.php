<?php

// PHP "Song cache scraper" for Stepmania
// https://github.com/DaveLinger/Stepmania-Stream-Tools
// This script scrapes your Stepmania cache directory for songs and posts each unique song to a mysql database table.
// It cleans [TAGS] from the song titles and it saves a "search ready" version of each song title (without spaces or special characters) to the "strippedtitle" column.
// This way you can have another script search/parse your entire song library - for example to make song requests.
// You only need to re-run this script any time you add new songs and Stepmania has a chance to build its cache. It'll skip songs that already exist in the DB.
// The same exact song title is allowed to exist in different packs.
//
// Run this from the command line like this: "php scrape.php"
//
// "Wouldn't it be nice" future features?:
// 
// 1. Extract data about each song such as number of steps/difficulty to save to DB.
// 2. Automatically upload each pack's banner to the remote server
// 3. Automatically upload each SONG's banner to the remote server (optional - this would use a lot of remote storage space)

// Configuration

//directory of chache\songs
$scrapedir = "C:/Users/[USERNAME]/AppData/Roaming/StepMania 5.1/Cache/Songs";

$dbhost = '';
$dbuser = '';
$dbpass = '';
$db = '';
$dbtable = "sm_songs";

// Code

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

$files = array ();
foreach(glob("{$scrapedir}/*", GLOB_BRACE) as $file) {
    $files[] = $file;
}

if(count($files) == 0){die("No files");}

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $db);   
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$installed_array = array();
$i=1;
$new_songs_added = 0;
$new_song_error = 0;

//reset "installed" field to FALSE so that we can have an acurate correlation between db and sm5
//once songs are found or added to the db, "installed" will be set to TRUE
$sql_clear = "UPDATE {$dbtable} SET installed = 0";
if (!mysqli_query($conn, $sql_clear)) {
		echo "Error: " . $sql_clear . "\n" . mysqli_error($conn);
	}

foreach ($files as $file){
	
	$data = "";
	$song_dir = "";
	$title = "";
	$subtitle = "";
	$artist = "";
	$pack = "";
	$display_bpm = "";
	$music_length = "";
	$bga = 0;
	$stepstype = "";
	$difficulty = "";
	$notedata_array = "";
	$lightschart = 0;
	
	//echo "Starting inspection of file $i :: $file\n";
	
	$data = file_get_contents($file);
	$data = " ".$data;
	
//Let's do a quick inital sanity check of the cache file to make sure it's valid.
//If not, skip processing the file and echo the error.
if((strpos($data,"#SONGFILENAME:")) && (strpos($data,"#NOTEDATA:"))){
//file looks good, let's continue...
	
	//Get song directory (this is needed to associate the songlist with score records)	
		
		if( (strpos($data,"#SONGFILENAME:") ) && (!strpos($data,"#SONGFILENAME:;")) ){
				//song has a an associated simfile
				//echo "directory to simfile\n";
				$filenamestart = strpos($data,"#SONGFILENAME:")+14;
				$nextsemicolon = strpos($data, ";", strpos($data,"#SONGFILENAME:")+14);
				$length = $nextsemicolon-$filenamestart;
				$song_dir = substr($data, $filenamestart, $length);
				$song_dir = substr($song_dir,1,strrpos($song_dir,"/")-1); //remove benginning slash and file extension
				//echo "\"$song_dir\"\n";
			}else{
				echo $file . "\n There's something truly wrong with this song, like how? \n";
			}
			
	//		
		
	//Get title
		
		if( (strpos($data,"#TITLETRANSLIT:;")) || (!strpos($data, "#TITLETRANSLIT:")) ){
			//song does not have a transliterated title
			//echo "song does not have a transliterated title\n";
			if( (strpos($data,"#TITLE:") ) && (!strpos($data,"#TITLE:;")) ){
				//song has a regular title
				//echo "song has a regular title\n";
				$titlestart = strpos($data,"#TITLE:")+7;
				$nextsemicolon = strpos($data, ";", strpos($data,"#TITLE:")+7);
				$length = $nextsemicolon-$titlestart;
				$title = substr($data, $titlestart, $length);
				//echo "\"$title\"\n";
			}else{
				//song doesn't have a title, can you believe that shit? Use the end of the filename.
				$title = substr($song_dir, strripos($song_dir, "/")+1);
			}
		}else{
			if(strpos($data,"#TITLETRANSLIT:")){
				//song has a transliterated title
				//echo "song has a transliterated title\n";
				$titlestart = strpos($data,"#TITLETRANSLIT:")+15;
				$nextsemicolon = strpos($data, ";", strpos($data,"#TITLETRANSLIT:")+15);
				$length = $nextsemicolon-$titlestart;
				$title = substr($data, $titlestart, $length);
				//echo "\"$title\"\n";
			}else{
				echo "!!!!! File must be busted. No title or titletranslit. !!!!!\n";
			}
		}
		
		if(strpos($title, "[") == 0 && strpos($title, "]") && !preg_match("/]$/",$title)){
			//This song title has a [BRACKETED TAG] before the actual title, let's remove it
			$firstbracketpos = strpos($title, "[");
			$lastbracketpos = strpos($title, "]");
			$title = substr($title, $lastbracketpos+1);
			
			if(strpos($title, "- ") == 1){
				//This song title now has a " - " before the actual title, let's remove that too
				$title = substr($title, 3);
			}
		}
		
		$title = trim($title);
		$title = addslashes($title);
		$strippedtitle = clean($title);
		
	//

	//Get subtitle
		
		if( (strpos($data,"#SUBTITLETRANSLIT:;")) || (!strpos($data, "#SUBTITLETRANSLIT:")) ){
			//song does not have a transliterated subtitle
			//echo "song does not have a transliterated subtitle\n";
			if( (strpos($data,"#SUBTITLE:") ) && (!strpos($data,"#SUBTITLE:;")) ){
				//song has a regular subtitle
				//echo "song has a regular subtitle\n";
				$subtitlestart = strpos($data,"#SUBTITLE:")+10;
				$nextsemicolon = strpos($data, ";", strpos($data,"#SUBTITLE:")+10);
				$length = $nextsemicolon-$subtitlestart;
				$subtitle = substr($data, $subtitlestart, $length);
				//echo "\"$subtitle\"\n";
			}
			
		}else{
			if(strpos($data,"#SUBTITLETRANSLIT:")){
				//song has a transliterated subtitle
				//echo "song has a transliterated subtitle\n";
				$subtitlestart = strpos($data,"#SUBTITLETRANSLIT:")+18;
				$nextsemicolon = strpos($data, ";", strpos($data,"#SUBTITLETRANSLIT:")+18);
				$length = $nextsemicolon-$subtitlestart;
				$subtitle = substr($data, $subtitlestart, $length);
				//echo "\"$subtitle\"\n";
			}
		}
		
		$subtitle = trim($subtitle);
		$subtitle = addslashes($subtitle);
		$strippedsubtitle = clean($subtitle);
		
	//

	//Get pack

		$pack = substr($song_dir, 0, strripos($song_dir, "/"));
		$pack = substr($pack, strripos($pack, "/")+1);
		//echo $pack . "\n";
		
	//

	//Get artist
		
		if( (strpos($data,"#ARTISTTRANSLIT:;")) || (!strpos($data, "#ARTISTTRANSLIT:")) ){
			//song does not have a transliterated artist
			if( (strpos($data,"#ARTIST:") ) && (!strpos($data,"#ARTIST:;")) ){
				//song has a regular artist
				$artiststart = strpos($data,"#ARTIST:")+8;
				$nextsemicolon = strpos($data, ";", strpos($data,"#ARTIST:")+8);
				$length = $nextsemicolon-$artiststart;
				$artist = substr($data, $artiststart, $length);
			}
		}else{
			if(strpos($data,"#ARTISTTRANSLIT:")){
				//song has a transliterated artist
				$artiststart = strpos($data,"#ARTISTTRANSLIT:")+16;
				$nextsemicolon = strpos($data, ";", strpos($data,"#ARTISTTRANSLIT:")+16);
				$length = $nextsemicolon-$artiststart;
				$artist = substr($data, $artiststart, $length);
			}else{
				echo "!!!!! File must be busted. No artist or artisttranslit. !!!!!\n";
			}
		}
		
		$artist = trim($artist);
		$artist = addslashes($artist);
		$strippedartist = clean($artist);
		
	//

	// Get BPM

		if( (strpos($data,"#DISPLAYBPM:") ) && (!strpos($data,"#DISPLAYBPM:;")) ){
			//song has a bpm listed
			$displaybpmstart = strpos($data,"#DISPLAYBPM:")+12;
			$nextsemicolon = strpos($data, ";", strpos($data,"DISPLAYBPM:")+12);
			$length = $nextsemicolon-$displaybpmstart;
			$display_bpm = substr($data, $displaybpmstart, $length);
		}else{
			if(strpos($data,"#BPMS:")){
			$displaybpmstart = strpos($data,"#BPMS:")+6;
			$displaybpmstart = strpos($data,"=")+1;
			$nextsemicolon = strpos($data, ";", strpos($data,"BPMS:")+6);
			$length = $nextsemicolon-$displaybpmstart;
			$display_bpm = substr($data, $displaybpmstart, $length);
			}
		}

		$display_bpm = trim($display_bpm);
		$display_bpm = intval($display_bpm,0);

	//

	// Get music length in seconds

		if( (strpos($data,"#MUSICLENGTH:") ) && (!strpos($data,"#MUSICLENGTH:;")) ){
			//song has a bpm listed
			$musiclengthstart = strpos($data,"#MUSICLENGTH:")+13;
			$nextsemicolon = strpos($data, ";", strpos($data,"#MUSICLENGTH:")+13);
			$length = $nextsemicolon-$musiclengthstart;
			$music_length = substr($data, $musiclengthstart, $length);
		}

		$music_length = trim($music_length);
		$music_length = intval($music_length,0);

	//

	//Get existence of background video
		
		if( (strpos($data,"#BGCHANGES:") ) && (!strpos($data,"#BGCHANGES:;")) ){
				$bga = 1;
			}
			
	//		

	// Get difficulties and meters for all step types
		
		//build "empty" array with interested notedata values.
		$notedata_array = array( 'dance-single'=> 
									array( 'Beginner'=>0, 'Easy'=>0, 'Medium'=>0, 'Hard'=>0, 'Challenge'=>0), 
								'dance-double'=> 
									array( 'Beginner'=>0, 'Easy'=>0, 'Medium'=>0, 'Hard'=>0, 'Challenge'=>0)
								);
												
		if( strpos($data,"#NOTEDATA:")){

			$notedata_total = substr_count($data,"#NOTEDATA:"); //how many step charts are there?
			$notedata_pos = 0;
			$notedata_count = 1;
			//start from the first occurance of notedata, set found data to array
			while ($notedata_count <= $notedata_total){ 
				$notedata_pos = strpos($data, "#NOTEDATA:",$notedata_pos)+10;
				
				$stepstypestart = strpos($data,"#STEPSTYPE:",$notedata_pos)+11;
				$nextsemicolon = strpos($data, ";", $stepstypestart);
				$length = $nextsemicolon-$stepstypestart;
				$stepstype = substr($data, $stepstypestart, $length);
				
				$difficultystart = strpos($data,"#DIFFICULTY:",$notedata_pos)+12;
				$nextsemicolon = strpos($data, ";", $difficultystart);
				$length = $nextsemicolon-$difficultystart;
				$difficulty = substr($data, $difficultystart, $length);
		
				$meterstart = strpos($data,"#METER:",$notedata_pos)+7;
				$nextsemicolon = strpos($data, ";", $meterstart);
				$length = $nextsemicolon-$meterstart;
				$meter = substr($data, $meterstart, $length);
				
				//build array of notedata meters, replacing the original "empty" array
				$notedata_array = array_replace_recursive( $notedata_array, array( $stepstype => array( $difficulty => $meter )));
				
				$notedata_count++;
			}
		
		}else{
			echo "Error: " . $song_dir . " - No note data found? How TF did this happen?\n";
		}

		//print_r ($notedata_array) . "\n";

	//

	//Get lightschart. This is generated by the lights builder program.

		if (array_key_exists("lights-cabinet",$notedata_array)){
			$lightschart = 1;
		}
		//echo $lightschart . "\n";

	//
		
		//echo "$title by $artist from $pack\n";
		
		//$sql = "SELECT * FROM {$dbtable} WHERE title=\"$title\" AND artist=\"$artist\" AND pack=\"$pack\" ";
		$sql = "SELECT * FROM {$dbtable} WHERE song_dir=\"$song_dir/\"";
		$retval = mysqli_query( $conn, $sql );
		
		if(mysqli_num_rows($retval) == 0){
			//This song doesn't yet exist in the db, let's add it!
			$installed = 1;
			$new_songs_added++;
			echo "Adding to DB: ".stripslashes($title)." from ".stripslashes($pack)." \n";
			$newsql = "INSERT INTO {$dbtable} (title, subtitle, artist, pack, added, strippedtitle, strippedartist, song_dir, display_bpm, music_length, bga, meter_bsp, meter_esp, meter_msp, meter_hsp, meter_csp, meter_bdp, meter_edp, meter_mdp, meter_hdp, meter_cdp, lightschart, installed) values (\"$title\", \"$subtitle\", \"$artist\", \"$pack\", NOW(), \"$strippedtitle\", \"$strippedartist\", \"$song_dir/\", {$display_bpm}, {$music_length}, {$bga}, {$notedata_array['dance-single']['Beginner']}, {$notedata_array['dance-single']['Easy']}, {$notedata_array['dance-single']['Medium']}, {$notedata_array['dance-single']['Hard']},  {$notedata_array['dance-single']['Challenge']}, {$notedata_array['dance-double']['Beginner']}, {$notedata_array['dance-double']['Easy']}, {$notedata_array['dance-double']['Medium']}, {$notedata_array['dance-double']['Hard']}, {$notedata_array['dance-double']['Challenge']}, {$lightschart}, {$installed})";	
			
			if (!mysqli_query($conn, $newsql)) {
				echo "Error: " . $newsql . "\n" . mysqli_error($conn) . "\n";
			}
			}else{
				//This song already exists in the db, skip adding it
				//...instead, we will mark the existing record as "installed"
				//build array of ids
				array_push($installed_array, mysqli_fetch_assoc($retval)["id"]);
			
			}
		
}else{
	//something is wrong with this file, skipping...
	$new_song_error++;
	echo "There was an error with: [".substr($file,strlen($scrapedir)+1)."]. No .sm file or NOTEDATA found! \n \n";
}
	
$i++;

}

// After scraping all songs, update the existing and new songs as "installed"
	if(!empty($installed_array)){
	$sql_update = "UPDATE {$dbtable} SET installed = 1 WHERE id IN (".implode(",",$installed_array).")";
	//echo $sql_update."\n";
		if (!mysqli_query($conn, $sql_update)) {
			echo "Error: " . $sql_update . "\n" . mysqli_error($conn);
		}
	}

//

//Let's show some stats!

$db_inactive = mysqli_fetch_assoc( mysqli_query( $conn, "SELECT COUNT(id) AS id FROM {$dbtable} WHERE installed=0" ) )['id'];
echo "Scraped {$i} cache file(s) and added {$new_songs_added} new song(s) to the existing ".count($installed_array)." songs in the database! \n";
echo "{$db_inactive} songs are marked as 'not installed' and there were errors with {$new_song_error} song(s). \n";

//

// Let's clean up the sm_songs db, removing records that are not installed, have never been requested, never played, or don't have a recorded score
	//echo "Purging song database and cleaning up...";
	//$sql_purge = "DELETE FROM sm_songs 
	//			WHERE NOT EXISTS(SELECT NULL FROM sm_requests WHERE sm_requests.song_id = sm_songs.id LIMIT 1) AND NOT EXISTS (SELECT NULL FROM sm_scores WHERE //sm_scores.song_id = sm_songs.id LIMIT 1) AND sm_songs.installed IS FALSE";
	//if (!mysqli_query($conn, $sql_purge)) {
	//		echo "Error: " . $sql_purge . "\n" . mysqli_error($conn);
	//	}

//


mysqli_close($conn);

?>
