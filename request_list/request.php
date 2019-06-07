<?php

   define('dbhost', '');
   define('dbuser', '');
   define('dbpass', '');
   define('db', '');

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

if(!isset($_GET["song"]) && !isset($_GET["songid"]) && !isset($_GET["cancel"])){
	die();
}

function request_song($song_id, $requestor){

	$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
	if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

	$sql0 = "SELECT COUNT(*) as total FROM sm_requests WHERE song_id = \"$song_id\" AND state <> \"canceled\" AND request_time > date_sub(now(), interval 1 hour);";
	$retval0 = mysqli_query( $conn, $sql0 );
	$row0 = mysqli_fetch_assoc($retval0);
	if($row0["total"] > 0){echo "That song has already been requested recently!"; die();}

        $sql = "INSERT INTO sm_requests (song_id, request_time, requestor) VALUES (\"$song_id\", NOW(), \"$requestor\")";
        $retval = mysqli_query( $conn, $sql );

}

   $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
   if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$user = $_GET["user"];

if(isset($_GET["cancel"])){

        $sql = "SELECT * FROM sm_requests WHERE requestor = \"$user\" AND state <> \"canceled\" ORDER BY request_time DESC LIMIT 1";
	$retval = mysqli_query( $conn, $sql );

        if (mysqli_num_rows($retval) == 1) {
                while($row = mysqli_fetch_assoc($retval)) {

			$request_id = $row["id"];
			$song_id = $row["song_id"];
                        $sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\" LIMIT 1";
                        $retval2 = mysqli_query( $conn, $sql2 );
			while($row2 = mysqli_fetch_assoc($retval2)){$title = $row2["title"];}
		        $sql3 = "UPDATE sm_requests SET state=\"canceled\" WHERE id = \"$request_id\"";
        		$retval3 = mysqli_query( $conn, $sql3 );
			echo "Canceled {$user}'s request for $title";

		}

	}else{
		echo "$user hasn't requested any songs!";
	}

die();
}

if(isset($_GET["songid"])){

	$song = $_GET["songid"];
        //lookup by ID and request it

        $sql = "SELECT * FROM sm_songs WHERE id = \"$song\"";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
    		while($row = mysqli_fetch_assoc($retval)) {
        		request_song($song, $user);
        		echo "$user requested " . $row["title"]. " from " . $row["pack"];
        		die();
    		}
	} else {
        	echo "Didn't find any songs matching that id!";
        	die();
}

die();
}

if(isset($_GET["song"])){$song = $_GET["song"];}

$song = clean($song);

	//Determine if there's a song with this exact title. If someone requested "Tsugaru", this would match "TSUGARU" but would not match "TSUGARU (Apple Mix)"
        $sql = "SELECT * FROM sm_songs WHERE strippedtitle=\"$song\" ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
        		request_song($row["id"], $user);
        		echo "$user requested " . $row["title"]. " from " . $row["pack"];
    		}
	die();
	//end exact match
	}

        $sql = "SELECT * FROM sm_songs WHERE strippedtitle LIKE \"%$song%\" ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

if (mysqli_num_rows($retval) == 1) {
    while($row = mysqli_fetch_assoc($retval)) {
	request_song($row["id"], $user);
        echo "$user requested " . $row["title"]. " from " . $row["pack"];
    }
die();
//end one match
}
//no one match
if (mysqli_num_rows($retval) > 0) {
	echo "Top matches (request with !requestid [song id]):\n";
	$i=1;
    while($row = mysqli_fetch_assoc($retval)) {
        if($i>3){die();}
	echo "[ ".$row["id"]. " > " .$row["title"]." from ".$row["pack"]." ]";
	$i++;
    }
} else {
    echo "Didn't find any songs matching that name!";
}

die();

?>
