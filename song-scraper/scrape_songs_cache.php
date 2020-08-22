<?php

// PHP "Song scraper" for Stepmania
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
// 2. Automatically upload each SONG's banner to the remote server (optional - this would use a lot of remote storage space)

// Configuration

include ('config.php');

// Code

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function parseMetadata($file) {
	$file_arr = array();
	$lines = array();
	$delimiter = ":";
	$eol = ";";
	
	$data = file_get_contents($file);
	$data = substr($data,0,strpos($data,"//-------"));
	
	$file_arr = preg_split("/{$eol}/",$data);
	//print_r($file_arr);
	
	foreach ($file_arr as $line){
		// if there is no $delimiter, set an empty string
			$line = trim($line);
			if (substr($line,0,1) == "#"){
				if (stripos($line,$delimiter)===FALSE){
					$key = $line;
					$value = "";
			// esle treat the line as normal with $delimiter
				}else{
					$key = substr($line,0,strpos($line,$delimiter));
					$value = substr($line,strpos($line,$delimiter)+1);
				}
			$lines[trim($key,'"')] = trim($value,'"');	
		}
	}
	
	return $lines;
}

function parseNotedata($file) {
	$file_arr = array();
	$lines = array();
	$delimiter = ":";
	$eol = ";";
	$notedata_array = array();
	
	$data = file_get_contents($file);
	if( strpos($data,"#NOTEDATA:")){
		$data = substr($data,strpos($data,"//-------"));
		$data = substr($data,strpos($data,"#"));
		
	//build "empty" array with interested notedata values.
			$notedata_array = array();
			
				$notedata_total = substr_count($data,"#NOTEDATA:"); //how many step charts are there?
				$notedata_offset = 0;
				$notedata_next = 0;
				$notedata_count = 1;
				//start from the first occurance of notedata, set found data to array
				while ($notedata_count <= $notedata_total){ 
					$notedata_offset = strpos($data, "#NOTEDATA:",$notedata_next);
					$notedata_next = strpos($data, "#NOTEDATA:",$notedata_offset + strlen("#NOTEDATA:"));
						if ($notedata_next === FALSE){
							$notedata_next = strlen($data);
						}
					
					$data_sub = substr($data,$notedata_offset,$notedata_next-$notedata_offset);
					$file_arr = "";
					$file_arr = preg_split("/{$eol}/",$data_sub);
					
					foreach ($file_arr as $line){
						$line = trim($line);
						//only process lines beginning with '#'
						if (substr($line,0,1) == "#"){
							// if there is no $delimiter, set an empty string
							if (stripos($line,$delimiter)===FALSE){
								$key = $line;
								$value = "";
						// esle treat the line as normal with $delimiter
							}else{
								$key = substr($line,0,strpos($line,$delimiter));
								$value = substr($line,strpos($line,$delimiter)+1);
							}
						// trim any quotes (messes up later queries)
						$lines[trim($key,'"')] = trim($value,'"');			
							}
					}
					
					//build array of notedata chart information
					
				//Not all chart files have these descriptors, so let's check if they exist to avoid notices/errors	
					array_key_exists('#CHARTNAME',$lines) 	? : $lines['#CHARTNAME']   = "";
					array_key_exists('#DESCRIPTION',$lines) ? : $lines['#DESCRIPTION'] = "";
					array_key_exists('#CHARTSTYLE',$lines)  ? : $lines['#CHARTSTYLE']  = "";
					array_key_exists('#CREDIT',$lines)      ? : $lines['#CREDIT']      = "";
					array_key_exists('#DISPLAYBPM',$lines)  ? : $lines['#DISPLAYBPM']  = "";
					
					$notedata_array[] = array('chartname' => $lines['#CHARTNAME'], 'steptype' => $lines['#STEPSTYPE'], 'description' => $lines['#DESCRIPTION'], 'chartstyle' => $lines['#CHARTSTYLE'], 'difficulty' => $lines['#DIFFICULTY'], 'meter' => $lines['#METER'], 'radarvalues' => $lines['#RADARVALUES'], 'credit' => $lines['#CREDIT'], 'displaybpm' => $lines['#DISPLAYBPM'], 'stepfilename' => $lines['#STEPFILENAME']);

					$notedata_count++;
				}
	}
	
	return $notedata_array;
}


$files = array ();
foreach(glob("{$cacheDir}/*", GLOB_BRACE) as $file) {
    $files[] = $file;
}

if(count($files) == 0){die("No files");}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);   
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$i=1;
$new_songs_added = 0;
$new_song_error = 0;
$updated_songs = 0;
$installed_array = array();

//reset "installed" field to FALSE so that we can have an acurate correlation between db and sm5
//this will temporarily make the songlist look empty. DON'T PANIC!
//once songs are found or added to the db, "installed" will be set to 1/TRUE
$sql_clear = "UPDATE sm_songs SET installed = 0";
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
	$notedata_array = array();
	$metadata = array();
	$file_hash = "";
	$stored_hash = "";
	
	//echo "Starting inspection of file $i :: $file\n";
	
	//get md5 hash of file to determine if there are any updates
	$file_hash = md5_file($file);
	
	$metadata = parseMetadata($file);
	//print_r($metadata);
	$notedata_array = parseNotedata($file);
	//print_r($notedata_array);

//Let's do a quick inital sanity check of the cache file to make sure it's valid.
//If not, skip processing the file and echo the error.
	if(isset($metadata['#SONGFILENAME']) && !empty($notedata_array)){
//file looks good, let's continue...

	//Get song directory (this is needed to associate the songlist with score records)	

		if(isset($metadata['#SONGFILENAME'])){
				//song has a an associated simfile
				//echo "directory to simfile\n";
				$song_dir = substr($metadata['#SONGFILENAME'],1,strrpos($metadata['#SONGFILENAME'],"/")-1); //remove benginning slash and file extension
				//echo "\"$song_dir\"\n";
			}else{
				echo $file . "\n There's something truly wrong with this song, like how? \n";
			}

	//		
	
	//Get title
		if( !isset($metadata['#TITLETRANSLIT'])){
			//song does not have a transliterated title
			If (isset($metadata['#TITLE'])){
				//song has a regular title
				$title = $metadata['#TITLE'];
			}else{
				//song doesn't have a title, can you believe that shit? Use the end of the filename.
				$title = substr($song_dir, strripos($song_dir, "/")+1);
			}
		}elseif( isset($metadata['#TITLETRANSLIT'])){
			//song has a transliterated title
				$title = $metadata['#TITLETRANSLIT'];
			}else{
				echo "!!!!! File must be busted. No title or titletranslit. !!!!!\n";
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
		
		if( !isset($metadata['#SUBTITLETRANSLIT'])){
			//song does not have a transliterated subtitle
			If (isset($metadata['#SUBTITLE'])){
				//song has a regular subtitle
				$subtitle = $metadata['#SUBTITLE'];
			}
		}elseif( isset($metadata['#SUBTITLETRANSLIT'])){
			//song has a transliterated subtitle
				$subtitle = $metadata['#SUBTITLETRANSLIT'];
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
		
		if( !isset($metadata['#ARTISTTRANSLIT'])){
			//song does not have a transliterated artist
			If (isset($metadata['#ARTIST'])){
				//song has a regular artist
				$artist = $metadata['#ARTIST'];
			}
		}elseif( isset($metadata['#ARTISTTRANSLIT'])){
			//song has a transliterated artist
				$artist = $metadata['#ARTISTTRANSLIT'];
			}
		
		
		$artist = trim($artist);
		$artist = addslashes($artist);
		$strippedartist = clean($artist);
		
	//

	// Get BPM

		if( isset($metadata['#DISPLAYBPM']) && !empty($metadata['#DISPLAYBPM'])){
			//song has a bpm listed
			$display_bpm = $metadata['#DISPLAYBPM'];
		}elseif( isset($metadata['#BPMS']) && !empty($metadata['#BPMS'])){
			$displaybpmstart = strpos($metadata['#BPMS'],"=")+1;
			$display_bpm = substr($metadata['#BPMS'],$displaybpmstart);
			}

		$display_bpm = trim($display_bpm);
		$display_bpm = intval($display_bpm,0);

	//

	// Get music length in seconds

		if( isset($metadata['#MUSICLENGTH'])){
			//song has a music length listed
			$music_length = $metadata['#MUSICLENGTH'];
		}

		$music_length = trim($music_length);
		$music_length = intval($music_length,0);

	//

	//Get existence of background video
		
		if( isset($metadata['#BGCHANGES']) && !empty($metadata['#BGCHANGES'])){
			//song has a background video
			$bga = 1;
		}
			
	//
		
		//echo "$title by $artist from $pack\n";
		//check if this song exists in the db
		$sql = "SELECT * FROM sm_songs WHERE song_dir=\"$song_dir/\"";
		$retval = mysqli_query( $conn, $sql );
		
		$sql_notedata_values = "";
		
		if(mysqli_num_rows($retval) == 0){
		//This song doesn't yet exist in the db, let's add it!
			$installed = 1;
			$new_songs_added++;
			echo "Adding to DB: ".stripslashes($title)." from ".stripslashes($pack)." \n";

			$sql_songs_query = "INSERT INTO sm_songs (title, subtitle, artist, pack, strippedtitle, strippedartist, song_dir, display_bpm, music_length, bga, installed, added, checksum) VALUES (\"$title\", \"$subtitle\", \"$artist\", \"$pack\", \"$strippedtitle\", \"$strippedartist\", \"$song_dir/\", {$display_bpm}, {$music_length}, {$bga}, {$installed}, NOW(), \"$file_hash\")";
			
			if (!mysqli_query($conn, $sql_songs_query)) {
				echo "Error: " . $sql_songs_query . "\n" . mysqli_error($conn) . "\n";
			}
		// Adding note data to sm_notedata DB:		
			$song_id = mysqli_insert_id($conn);
			
			//build notedata array into query ready values
			foreach ($notedata_array as $key){
				$sql_notedata_values = $sql_notedata_values.",(\"$song_id\",\"$song_dir/\",\"".implode("\",\"",$key)."\",NOW())";
			}
				
			//remove beginning comma and concat to sql query string
			$sql_notedata_query = "INSERT INTO sm_notedata (song_id, song_dir, chart_name, stepstype, description, chartstyle, difficulty, meter, radar_values, credit, display_bpm, stepfile_name, datetime) VALUES ".substr($sql_notedata_values,1);
			
			if (!mysqli_query($conn, $sql_notedata_query)) {
				echo "Error: " . $sql_notedata_query . "\n" . mysqli_error($conn) . "\n";
			}
		}else{
				//This song already exists in the db, checking if there are any updates
				$retval = mysqli_fetch_assoc($retval);
				$song_id = $retval['id'];
				$stored_hash = $retval['checksum'];
				
				if( $file_hash != $stored_hash){
				// md5s do not match, assume there were updates to this song
					//echo "File Hash: ".$file_hash." != Stored Hash: ".$stored_hash."\n";
					$updated_songs++;
					$sql_songs_query = "UPDATE sm_songs SET 
					title=\"$title\", subtitle=\"$subtitle\", artist=\"$artist\", pack=\"$pack\", strippedtitle=\"$strippedtitle\", strippedartist=\"$strippedartist\", display_bpm={$display_bpm}, music_length={$music_length}, bga={$bga}, installed=1, checksum=\"$file_hash\" 
					WHERE id={$song_id}";
			
				echo "Changes detected in {$song_id}: ".stripslashes($title)." from ".stripslashes($pack)." Updating...\n";
			
					if (!mysqli_query($conn, $sql_songs_query)) {
						echo "Error: " . $sql_songs_query . "\n" . mysqli_error($conn) . "\n";
					}
				
					//whether song db updates or not, delete and insert notedata for song_id
					foreach ($notedata_array as $key){
						$sql_notedata_values = $sql_notedata_values.",(\"$song_id\",\"$song_dir/\",\"".implode("\",\"",$key)."\",NOW())";
					}
					
					$sql_notedata_query = "DELETE FROM sm_notedata WHERE song_id={$song_id}";
					
					if (!mysqli_query($conn, $sql_notedata_query)) {
						echo "Error: " . $sql_notedata_query . "\n" . mysqli_error($conn) . "\n";
					}
					
					$sql_notedata_query = "INSERT INTO sm_notedata (song_id, song_dir, chart_name, stepstype, description, chartstyle, difficulty, meter, radar_values, credit, display_bpm, stepfile_name, datetime) VALUES ".substr($sql_notedata_values,1); 
					
					if (!mysqli_query($conn, $sql_notedata_query)) {
						echo "Error: " . $sql_notedata_query . "\n" . mysqli_error($conn) . "\n";
					}
				}
						
				//we will mark the existing record as "installed"
				//build array of ids
				array_push($installed_array, $retval['id']);	
		}
		
}else{
	//something is wrong with this file, skipping...
	$new_song_error++;
	echo "There was an error with: [".substr($file,strlen($cacheDir)+1)."]. No chartfile or NOTEDATA found! Skipping...\n";
}
	
$i++;

}

// After scraping all songs, update the existing and new songs as "installed"
	if(!empty($installed_array)){
	$sql_update = "UPDATE sm_songs SET installed = 1 WHERE id IN (".implode(",",$installed_array).")";
	//echo $sql_update."\n";
		if (!mysqli_query($conn, $sql_update)) {
			echo "Error: " . $sql_update . "\n" . mysqli_error($conn);
		}
	}

//

//Let's show some stats!

$db_inactive = mysqli_fetch_assoc( mysqli_query( $conn, "SELECT COUNT(id) AS id FROM sm_songs WHERE installed=0" ) )['id'];
echo "Scraped {$i} cache file(s) adding {$new_songs_added} new song(s) and updating {$updated_songs} song(s) to the existing ".count($installed_array)." songs in the database! \n";
echo "{$db_inactive} song(s) marked as 'not installed' and there were errors with {$new_song_error} song(s). \n";

//

// Let's clean up the sm_songs db, removing records that are not installed, have never been requested, never played, or don't have a recorded score
	//echo "Purging song database and cleaning up...";
	//$sql_purge = "DELETE FROM sm_songs 
	//			WHERE NOT EXISTS(SELECT NULL FROM sm_requests WHERE sm_requests.song_id = sm_songs.id LIMIT 1) AND NOT EXISTS (SELECT NULL FROM sm_scores WHERE //sm_scores.song_id = sm_songs.id LIMIT 1) AND sm_songs.installed<>1";
	//if (!mysqli_query($conn, $sql_purge)) {
	//		echo "Error: " . $sql_purge . "\n" . mysqli_error($conn);
	//	}

//


mysqli_close($conn);

?>