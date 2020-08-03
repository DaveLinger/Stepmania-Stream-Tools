<?php

include("includes/config.php");

if(!isset($_GET["security_key"])){
        die("Fuck off");
}

if($_GET["security_key"] != $security_key){
        die("Fuck off");
}

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

if(!isset($_GET["user"])){
	echo "Error";
	die();
}

function request_random($requestor,$count){

if($count > 3){
	echo "Can't request that many songs at once!";
	die();
}

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $songlist = Array();

        $sql0 = "SELECT song_id, COUNT(*) occurrences FROM sm_requests WHERE state <> \"canceled\" GROUP BY song_id HAVING COUNT(*) > 1 ORDER BY COUNT(*) DESC";
        $retval0 = mysqli_query( $conn, $sql0 );

        $requeststotal = mysqli_num_rows($retval0);

        while($row0 = mysqli_fetch_assoc($retval0)) {
                $songid = $row0["song_id"];
                if(!array_key_exists($songid,$songlist)){
                        $songlist[$songid]["title"] = "";
                }
        }

        $sql1 = "SELECT songid, title, COUNT(*) occurrences FROM sm_songsplayed WHERE songid <> 0 GROUP BY songid HAVING COUNT(*) > 2 ORDER BY COUNT(*) DESC";
        $retval1 = mysqli_query( $conn, $sql1 );

	$playedtotal = mysqli_num_rows($retval1);

	$skippedsongs = 0;
        while($row1 = mysqli_fetch_assoc($retval1)) {
                $songid = $row1["songid"];
                $title = $row1["title"];
		if(!array_key_exists($songid,$songlist)){
                        $songlist[$songid]["title"] = $title;
                }
        }

$j=0;

while($j < $count){

        $good = "no";
        while($good == "no"){

		$requesttry = array_rand($songlist);

                $sql2 = "SELECT COUNT(*) as total FROM sm_requests WHERE song_id = \"$requesttry\" AND state <> \"canceled\" AND request_time > date_sub(now(), interval 1 hour);";
                $retval2 = mysqli_query( $conn, $sql2 );
                $row2 = mysqli_fetch_assoc($retval2);
                if($row2["total"] == 0 && !in_array($requesttry,$banned)){

                        $good = "yes";
                        $sql = "INSERT INTO sm_requests (song_id, request_time, requestor) VALUES (\"$requesttry\", NOW(), \"$requestor\")";
                        $retval = mysqli_query( $conn, $sql );

                        $sql1 = "SELECT * FROM sm_songs WHERE id = \"$requesttry\" LIMIT 1";
                        $retval1 = mysqli_query( $conn, $sql1 );
                        $row1 = mysqli_fetch_assoc($retval1);

                        echo "$requestor random request " . $row1["title"]. " from " . $row1["pack"]."\n";

		}

	}

$j++;

}

}

if($_GET["count"] != ""){

	request_random($_GET["user"],$_GET["count"]);

}else{

	request_random($_GET["user"],1);

}

?>
