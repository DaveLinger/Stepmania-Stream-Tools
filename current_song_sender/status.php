<?php

//This is the php script on the remote web server that the "send current song" python script sends its data to.

include ('../config.php');

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
    die("Fuck off");
}

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
   $string = mysqli_real_escape_string($conn, $string); // Removes sql injection atempts.
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

if(isset($_GET["data"])){

	$data = json_decode($_GET["data"], 1);

if(!isset($data["song"])){echo "Cleared"; die();}

	$song_dir = $data["dir"];
	$player = $data["player"];

        $sql = "SELECT * FROM sm_songs WHERE song_dir = \"$song_dir\"";
        $retval = mysqli_query( $conn, $sql );

        if (mysqli_num_rows($retval) == 1) {
		//matched a song ID
        $row = mysqli_fetch_assoc($retval);
		$song_id = $row["id"];
		$title = $row["title"];
		$pack = $row["pack"];

        	$sql = "SELECT * FROM sm_requests WHERE song_id = \"$song_id\" ORDER BY request_time DESC LIMIT 1";
        	$retval = mysqli_query( $conn, $sql );

        	if (mysqli_num_rows($retval) == 1) {
                	//matched a request ID
                	$row = mysqli_fetch_assoc($retval); 
                	$request_id = $row["id"];
        	}else{
                	$request_id = "0";
        	}

	}else{
		$song_id = "0";
	}

        $sql = "INSERT INTO sm_songsplayed (song_id, request_id, played) VALUES (\"$song_id\", \"$request_id\", NOW())";
        $retval = mysqli_query( $conn, $sql );

	echo "Added row to db for ".$title." from ".$pack;

}else{
	echo "No data present";
}

?>
