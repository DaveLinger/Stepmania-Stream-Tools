<?php

include("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
    die("Fuck off");
}

if(!isset($_GET["bansong"]) && !isset($_GET["bansongid"]) && !isset($_GET["user"])){
	die();
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
   $string = mysqli_real_escape_string($conn, $string); // Removes sql injection atempts.
}

function toggle_ban_song($song){

    global $conn;

	$sql0 = "SELECT * FROM sm_songs WHERE id = \"$song\"";
        $retval0 = mysqli_query( $conn, $sql0 );

        if(mysqli_num_rows($retval0) == 1){
            $row0 = mysqli_fetch_assoc($retval0);
            $title = $row0["title"];
            $banned = $row0["banned"];
		if($banned == "1"){
			$value = "0";
			$response = "Unbanned $title from $pack";
		}else{
			$value = "1";
			$response = "Banned $title from $pack";
		}

	        $sql = "UPDATE sm_songs SET banned={$value} WHERE id={$song} LIMIT 1";
        	$retval = mysqli_query( $conn, $sql );

		echo "$response";

	}else{
		echo "Something went wrong.";
	}

}

//die if the command did not come from the broadcaster
$user = $_GET["user"];
$broadcaster = $_GET["broadcaster"];
if(strtolower($user)!==$broadcaster){die("That's gonna be a no from me, dawg.");}

if(isset($_GET["bansongid"])){
	$song = $_GET["bansongid"];
        //lookup by ID

        $sql = "SELECT * FROM sm_songs WHERE id = '{$song}' ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
    		while($row = mysqli_fetch_assoc($retval)) {
        		toggle_ban_song($song);
        		die();
    		}
	} else {
        	echo "Didn't find any songs matching that id!";
        	die();
}

die();
}

if(isset($_GET["bansong"])){
	$song = $_GET["bansong"];
	$song = clean($song);

	//Determine if there's a song with this exact title. If someone requested "Tsugaru", this would match "TSUGARU" but would not match "TSUGARU (Apple Mix)"
        $sql = "SELECT * FROM sm_songs WHERE strippedtitle='{$song}' ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
        		toggle_ban_song($row["id"]);
    		}
	die();
	//end exact match
	}

        $sql = "SELECT * FROM sm_songs WHERE strippedtitle LIKE '%{$song}%' ORDER BY title ASC, pack ASC";
        $retval = mysqli_query( $conn, $sql );

if (mysqli_num_rows($retval) == 1) {
    while($row = mysqli_fetch_assoc($retval)) {
	toggle_ban_song($row["id"]);
    }
die();
//end one match
}
//no one match
if (mysqli_num_rows($retval) > 0) {
	echo "No exact match (!bansongid [id]):\n";
	$i=1;
    while($row = mysqli_fetch_assoc($retval)) {
        if($i>4){die();}
	echo "[ ".$row["id"]. " > " .trim($row["title"]." ".$row["subtitle"])." from ".$row["pack"]." ]";
	$i++;
    }
}

die();
}

?>
