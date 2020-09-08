<?php

include ("config.php");

if (!isset($_POST['security_key']) || $_POST['security_key'] != $security_key){die("Fuck off");}

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

if(isset($_GET["data"])){

	$data = json_decode($_GET["data"], 1);

if(!isset($data["song"])){echo "Cleared"; die();}

        $title = $data["song"];

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
	$pack = $data["pack"];
	$artist = $data["artist"];
	$diff = $data["diff"];
	$steps = $data["steps"];
	$time = $data["time"];

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $sql = "SELECT * FROM sm_songs WHERE strippedtitle = \"$strippedtitle\" AND pack = \"$pack\"";
        $retval = mysqli_query( $conn, $sql );

        if (mysqli_num_rows($retval) == 0) {

		$total_strlen = strlen($title);
		if(strpos($title,")")){
			//String ends with a closing parenthesis, let's remove that part
			$startparenthesis = strpos($title,"(");
			$title = substr($title,0,$startparenthesis-1);
			$strippedtitle = clean($title);
		}

	        $sql = "SELECT * FROM sm_songs WHERE strippedtitle = \"$strippedtitle\" AND pack = \"$pack\"";
	        $retval = mysqli_query( $conn, $sql );

	}

        if (mysqli_num_rows($retval) == 1) {
		//matched a song ID
                $row = mysqli_fetch_assoc($retval);
		$song_id = $row["id"];

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

        $sql = "INSERT INTO sm_songsplayed (songid, requestid, title, artist, pack, played) VALUES (\"$song_id\", \"$request_id\", \"$title\", \"$artist\", \"$pack\", NOW())";
        $retval = mysqli_query( $conn, $sql );

	echo "Added row to db";

}else{
	echo "No data present";
}

?>
