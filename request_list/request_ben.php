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

if(strtolower($_GET["user"]) != "ddrdave" && strtolower($_GET["user"]) != "benspeirs"){
	echo "You're not Ben Speirs!";
	die();
}

function request_ben($requestor){

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $songlist = Array();

        $sql0 = "SELECT * FROM sm_songs WHERE pack = \"Ben Speirs' SPEIRMIX VS Pump It Up\" OR pack = \"Ben Speirs' SPEIRMIX RESPECT\" OR pack = \"Ben Speirs' SPEIRMIX GALAXY\" OR pack = \"Ben Speirs Commissions (DDRDave)\" OR pack = \"Ben's New Commissions\" ORDER BY RAND()";
        $retval0 = mysqli_query( $conn, $sql0 );

        $requeststotal = mysqli_num_rows($retval0);

        while($row0 = mysqli_fetch_assoc($retval0)) {
                $songid = $row0["id"];
                if(!array_key_exists($songid,$songlist)){
                        $songlist[$songid]["title"] = "";
                }
        }

        $good = "no";
        while($good == "no"){

		$requesttry = array_rand($songlist);

                $sql2 = "SELECT COUNT(*) as total FROM sm_requests WHERE song_id = \"$requesttry\" AND state <> \"canceled\" AND request_time > date_sub(now(), interval 1 hour);";
                $retval2 = mysqli_query( $conn, $sql2 );
                $row2 = mysqli_fetch_assoc($retval2);
                if($row2["total"] == 0){

                        $good = "yes";
                        $sql = "INSERT INTO sm_requests (song_id, request_time, requestor) VALUES (\"$requesttry\", NOW(), \"$requestor\")";
                        $retval = mysqli_query( $conn, $sql );

                        $sql1 = "SELECT * FROM sm_songs WHERE id = \"$requesttry\" LIMIT 1";
                        $retval1 = mysqli_query( $conn, $sql1 );
                        $row1 = mysqli_fetch_assoc($retval1);

                        echo "$requestor requested " . $row1["title"]. " from " . $row1["pack"]."\n";

		}

	}

}

	request_ben($_GET["user"],1);

?>
