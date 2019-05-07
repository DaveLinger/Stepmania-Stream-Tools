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

// Configuration

$scrapedir = "C:\Users\Dave\AppData\Roaming\StepMania 5/Songs";

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

foreach ($files as $file){

	$folder = substr($file, 0, strripos($file, "/"));
	$title = substr($folder, strripos($folder, "/")+1);
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
	$strippedtitle = clean($title);
	
	$pack = substr($file, 0, strripos($file, "/"));
	$pack = substr($pack, 0, strripos($pack, "/"));
	$pack = substr($pack, strripos($pack, "/")+1);

	$sql = "SELECT * FROM sm_songs WHERE title=\"$title\" AND pack=\"$pack\" ";
	$retval = mysqli_query( $conn, $sql );

	if(mysqli_num_rows($retval) == 0){
		//This song doesn't yet exist in the db, let's add it
		echo "adding this song: $title from $pack\n";
		$newsql = "INSERT INTO sm_songs (title, pack, added, strippedtitle) values (\"$title\", \"$pack\", NOW(), \"$strippedtitle\")";
		if (!mysqli_query($conn, $newsql)) {
			echo "Error: " . $newsql . "<br>" . mysqli_error($conn);
		}
	}else{
		//This song already exists in the db, skip adding it
	}

}

mysqli_close($conn);

?>
