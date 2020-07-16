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

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $songlist = Array();

        $sql1 = "SELECT songid, title, COUNT(*) as occurrences FROM sm_songsplayed WHERE songid <> 0 GROUP BY songid ORDER BY occurrences DESC LIMIT 50";
        $retval1 = mysqli_query( $conn, $sql1 );

        while($row1 = mysqli_fetch_assoc($retval1)) {
                $songid = $row1["songid"];
                $title = $row1["title"];
		if(!array_key_exists($songid,$songlist)){
                        $songlist[$songid]["title"] = $title;
                }
        }

        $good = "no";
	$count == "0";
        while($good == "no"){
		$count++;
		if($count > 25){echo "Ran out of top songs!"; die();}
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

                        echo "$requestor top request " . $row1["title"]. " from " . $row1["pack"]."\n";

		}

	}

}

	request_random($_GET["user"]);

?>
