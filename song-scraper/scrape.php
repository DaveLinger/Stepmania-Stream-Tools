<?php

// PHP "Song scraper" for Stepmania
// https://github.com/DaveLinger/Stepmania-Stream-Tools
// This script scrapes your Stepmania songs directory for songs and posts each unique song to a mysql database table.
// It cleans [TAGS] from the song titles and it saves a "search ready" version of each song title (without spaces or special characters) to the "strippedtitle" column.
// This way you can have another script search/parse your entire song library - for example to make song requests.
// You only need to re-run this script any time you add new songs. It'll skip songs that already exist in the DB.
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

$scrapedir = "C:/Users/Dave/AppData/Roaming/Stepmania 5/Songs";

$dbhost = '';
$dbuser = '';
$dbpass = '';
$db = "";

// Code

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function findFiles($directory) {
	
function glob_recursive($directory, &$directories = array()) {
	if(substr($directory, -2) != "/." && substr($directory, -3) != "/.."){
		foreach(glob($directory, GLOB_ONLYDIR | GLOB_NOSORT) as $folder) {
			if(substr($folder, -2) != "/." && substr($folder, -3) != "/.."){
				$directories[] = $folder;
				glob_recursive("{$folder}/*", $directories);
				glob_recursive("{$folder}/.*", $directories);
			}
		}
	}
}

    glob_recursive($directory, $directories);
    $files = array ();
    foreach($directories as $directory) {
		$directory = str_replace(['[',']',"\f[","\f]"], ["\f[","\f]",'[[]','[]]'], $directory);
		foreach(glob("{$directory}/*.{sm,ssc,dwi}", GLOB_BRACE) as $file) {
            $files[] = $file;
        }
	foreach(glob("{$directory}/.*.{sm,ssc,dwi}", GLOB_BRACE) as $dotfile) {
            $dotfiles[] = $dotfile;
        }
    }
	
	if($dotfiles){$files = array_merge($files,$dotfiles);}

    return $files;
}

$files = findFiles($scrapedir);

if(count($files) == 0){die("No files");}

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $db);   
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$alreadydone = Array();
$i=1;

foreach ($files as $file){
	
	$data = "";
	$title= "";
	$artist = "";
	$pack = "";
	
	//echo "Starting inspection of file $i :: $file\n";
	
	$data = file_get_contents($file);
	$data = " ".$data;
	
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
			//song doesn't have a title, can you believe that shit? Use the folder name.
			$folder = substr($file, 0, strripos($file, "/"));
			$title = substr($folder, strripos($folder, "/")+1);
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
	
	if(strpos($title, "[") == 0 && strpos($title, "]")){
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

//Get pack

	$pack = substr($file, 0, strripos($file, "/"));
	$pack = substr($pack, 0, strripos($pack, "/"));
	$pack = substr($pack, strripos($pack, "/")+1);

//

//Get artist
	
	if( (strpos($data,"#ARTISTTRANSLIT:;")) || (!strpos($data, "#ARTISTTRANSLIT:")) ){
		//song does not have a transliterated artist
		if( (strpos($data,"#ARTIST:") ) && (!strpos($data,"#ARTIST:;")) ){
			//song has a regular artist
			$titlestart = strpos($data,"#ARTIST:")+8;
			$nextsemicolon = strpos($data, ";", strpos($data,"#ARTIST:")+8);
			$length = $nextsemicolon-$titlestart;
			$artist = substr($data, $titlestart, $length);
		}
	}else{
		if(strpos($data,"#ARTISTTRANSLIT:")){
			//song has a transliterated artist
			$titlestart = strpos($data,"#ARTISTTRANSLIT:")+16;
			$nextsemicolon = strpos($data, ";", strpos($data,"#ARTISTTRANSLIT:")+16);
			$length = $nextsemicolon-$titlestart;
			$artist = substr($data, $titlestart, $length);
		}else{
			echo "!!!!! File must be busted. No artist or artisttranslit. !!!!!\n";
		}
	}
	
	$artist = trim($artist);
	$artist = addslashes($artist);
	$strippedartist = clean($artist);
	
//

if(in_array("$strippedtitle:$pack", $alreadydone)){
	//This song has already been processed, skip it. (This happens when a song has a SM *and* a SSC file for example)
}else{
	array_push($alreadydone,"$strippedtitle:$pack");
	
	//echo "$title by $artist from $pack\n";
	
	$sql = "SELECT * FROM sm_songs WHERE title=\"$title\" AND artist=\"$artist\" AND pack=\"$pack\" ";
	$retval = mysqli_query( $conn, $sql );
	
	if(mysqli_num_rows($retval) == 0){
		//This song doesn't yet exist in the db, let's add it
		echo "Adding to DB: $title from $pack\n";
		$newsql = "INSERT INTO sm_songs (title, artist, pack, added, strippedtitle, strippedartist) values (\"$title\", \"$artist\", \"$pack\", NOW(), \"$strippedtitle\", \"$strippedartist\")";
		if (!mysqli_query($conn, $newsql)) {
			echo "Error: " . $newsql . "<br>" . mysqli_error($conn);
		}
	}else{
		//This song already exists in the db, skip adding it
	}
}
$i++;

}

mysqli_close($conn);

?>
