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

if(!isset($_GET["song"]) && !isset($_GET["songid"]) && !isset($_GET["cancel"]) && !isset($_GET["skip"])){
	die();
}

function add_user($userid, $user){

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $sql = "INSERT INTO sm_requestors (twitchid, name, dateadded) VALUES (\"$userid\", \"$user\", NOW())";
        $retval = mysqli_query( $conn, $sql );
	$the_id = mysqli_insert_id($conn);

	return($the_id);

}

function check_user($userid, $user){

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $sql0 = "SELECT * FROM sm_requestors WHERE twitchid = \"$userid\"";
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0);

        if($numrows != 0){
		//User exists in DB, return data
		$row0 = mysqli_fetch_assoc($retval0);
		$id = $row0["id"];
		$twitchid = $row0["twitchid"];
		$whitelisted = $row0["whitelisted"];
		$banned = $row0["banned"];
        }else{
		//User is new - create then return data
		$id = add_user($userid, $user);
		$whitelisted = "false";
		$banned = "false";
	}

	$userobj["id"] = "$id";
	$userobj["name"] = "$user";
	$userobj["twitchid"] = "$userid";
	$userobj["whitelisted"] = "$whitelisted";
	$userobj["banned"] = "$banned";

	return($userobj);

}

function check_cooldown($user){

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

        $sql0 = "SELECT * FROM sm_requests WHERE state <> \"canceled\" AND requestor = \"$user\" AND request_time > DATE_SUB(NOW(), INTERVAL 4 MINUTE)";
        $retval0 = mysqli_query( $conn, $sql0 );
	$numrows = mysqli_num_rows($retval0);
	if($numrows != 0){
		echo "You are requesting songs too rapidly!";
		die();
	}
}

function request_random($requestor,$twitchid){

$userobj = check_user($twitchid, $requestor);

if($userobj["banned"] == "true"){
	die();
}
if($userobj["whitelisted"] != "true"){
	check_cooldown($requestor);
} 

        $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
        if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

	$good = "no";
	while($good == "no"){

        	$sql0 = "SELECT song_id, COUNT(*) occurrences FROM sm_requests WHERE state <> \"canceled\" GROUP BY song_id HAVING COUNT(*) > 1 ORDER BY RAND() LIMIT 1";
        	$retval0 = mysqli_query( $conn, $sql0 );
        	$row0 = mysqli_fetch_assoc($retval0);
                $songid = $row0["song_id"];

	        $sql2 = "SELECT COUNT(*) as total FROM sm_requests WHERE song_id = \"$songid\" AND state <> \"canceled\" AND request_time > date_sub(now(), interval 1 hour);";
        	$retval2 = mysqli_query( $conn, $sql2 );
        	$row2 = mysqli_fetch_assoc($retval2);
        	if($row2["total"] == 0 && !in_array($songid,$banned)){
	
			$good = "yes";
        		$sql = "INSERT INTO sm_requests (song_id, request_time, requestor) VALUES (\"$songid\", NOW(), \"$requestor\")";
        		$retval = mysqli_query( $conn, $sql );

	                $sql1 = "SELECT * FROM sm_songs WHERE id = \"$songid\" LIMIT 1";
        	        $retval1 = mysqli_query( $conn, $sql1 );
                	$row1 = mysqli_fetch_assoc($retval1);

			echo "$requestor random request " . $row1["title"]. " from " . $row1["pack"];
			die();
		}
	}

//

	request_song($songid,$requestor,$twitchid);
	echo "$requestor requested " . $row1["title"]. " from " . $row1["pack"];

}

function request_song($song_id, $requestor, $twitchid){

$userobj = check_user($twitchid, $requestor);

if($userobj["banned"] == "true"){
        die();
}   
if($userobj["whitelisted"] != "true"){
        check_cooldown($requestor);
	if (in_array($song_id, $GLOBALS["banned"])) {
        	echo "Song can't be requested. Sorry!";
        	die();
	}
}

	$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
	if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

	$sql0 = "SELECT COUNT(*) as total FROM sm_requests WHERE song_id = \"$song_id\" AND state <> \"canceled\" AND request_time > date_sub(now(), interval 1 hour);";
	$retval0 = mysqli_query( $conn, $sql0 );
	$row0 = mysqli_fetch_assoc($retval0);
	if(($row0["total"] > 0) && ($userobj["whitelisted"] != "true")){echo "That song has already been requested recently!"; die();}

        $sql = "INSERT INTO sm_requests (song_id, request_time, requestor) VALUES (\"$song_id\", NOW(), \"$requestor\")";
        $retval = mysqli_query( $conn, $sql );

}

   $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
   if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$user = $_GET["user"];
if(isset($_GET["userid"])){
	$twitchid = $_GET["userid"];
}else{
	$twitchid = "";
}

$userobj = check_user($twitchid, $user);

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

if(isset($_GET["skip"])){

        $sql = "SELECT * FROM sm_requests WHERE state <> \"canceled\" ORDER BY request_time DESC LIMIT 1";
        $retval = mysqli_query( $conn, $sql );

                while($row = mysqli_fetch_assoc($retval)) {

                        $request_id = $row["id"];
                        $song_id = $row["song_id"];
                        $sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\" LIMIT 1";
                        $retval2 = mysqli_query( $conn, $sql2 );
                        while($row2 = mysqli_fetch_assoc($retval2)){$title = $row2["title"];}
                        $sql3 = "UPDATE sm_requests SET state=\"canceled\" WHERE id = \"$request_id\"";
                        $retval3 = mysqli_query( $conn, $sql3 );
                        echo "$user skipped $title";

                }

die();
}

if(isset($_GET["songid"])){
	$songid = $_GET["songid"];

        $sql = "SELECT * FROM sm_songs WHERE id=\"$songid\" ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
   		while($row = mysqli_fetch_assoc($retval)) {
        	request_song($songid, $user, $twitchid);
     		echo "$user requested " . $row["title"]. " from " . $row["pack"];
	    	}
	}
	die();
}

if(isset($_GET["song"])){
    $song = $_GET["song"];
    $song = clean($song);

if(strcasecmp($song, "random") == 0){
	request_random($user,$twitchid);
	die();
}

	//Determine if there's a song with this exact title. If someone requested "Tsugaru", this would match "TSUGARU" but would not match "TSUGARU (Apple Mix)"
        $sql = "SELECT * FROM sm_songs WHERE strippedtitle=\"$song\" ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
        		request_song($row["id"], $user, $twitchid);
        		echo "$user requested " . $row["title"]. " from " . $row["pack"];
    		}
	die();
	//end exact match
	}

        $sql = "SELECT * FROM sm_songs WHERE strippedtitle LIKE \"%$song%\" ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

if (mysqli_num_rows($retval) == 1) {
    while($row = mysqli_fetch_assoc($retval)) {
	request_song($row["id"], $user, $twitchid);
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
    if(is_numeric($song)){echo " (Did you mean !requestid $song?)";}
}

die();

}
?>
