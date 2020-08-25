<?php

include("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
    die("Fuck off");
}

function clean($string) {
	global $conn;
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
   $string = mysqli_real_escape_string($conn, $string); // Removes sql injection atempts.
}

if(!isset($_GET["song"]) && !isset($_GET["songid"]) && !isset($_GET["cancel"]) && !isset($_GET["skip"])){
	die();
}

function add_user($userid, $user){

	global $conn;
	$sql = "INSERT INTO sm_requestors (twitchid, name, dateadded) VALUES (\"$userid\", \"$user\", NOW())";
	$retval = mysqli_query( $conn, $sql );
	$the_id = mysqli_insert_id($conn);

	return($the_id);

}

function check_user($userid, $user){

        global $conn;
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

function check_length(){
	global $conn;
	$sql0 = "SELECT state FROM sm_requests ORDER BY request_time DESC LIMIT 10";
    $retval0 = mysqli_query( $conn, $sql0 );
	$length = 0;
	foreach($retval0 as $row){
		if($row['state'] == 'requested'){
			$length++;
		}
	}
	return $length;
}

function check_cooldown($user){

	//check total of active requests. stop at +10
	$length = check_length();
	
	if($length > 10){
		die("Too many songs on the request list! Try again in a few minutes.");
	}
    $interval = 0.5 * $length;
		
		global $conn;
        $sql0 = "SELECT * FROM sm_requests WHERE state <> \"canceled\" AND requestor = \"$user\" AND request_time > DATE_SUB(NOW(), INTERVAL {$interval} MINUTE)";
        $retval0 = mysqli_query( $conn, $sql0 );
	$numrows = mysqli_num_rows($retval0);
	if($numrows != 0){
		die("Slow down there, part'ner! Try again in ".ceil($interval)." minutes");
	}
}

function check_banned($song_id){

        global $conn;
        $sql0 = "SELECT * FROM sm_songs WHERE installed=1 AND id = '{$song_id}' LIMIT 1";
		if( mysqli_fetch_assoc( mysqli_query( $conn,$sql0))['banned'] == 1)
			{
			die("This song was put on the naughty list and cannot be requested!");
			}
}

function request_song($song_id, $requestor, $tier, $twitchid, $broadcaster){
	
	$userobj = check_user($twitchid, $requestor);

	if($userobj["banned"] == "true"){
        die();
	}   
	if($userobj["whitelisted"] != "true"){
        check_cooldown($requestor);
		}

	global $conn;
	
	check_banned($song_id);

	$sql0 = "SELECT COUNT(*) AS total FROM sm_requests WHERE song_id = '{$song_id}' AND state <> 'canceled' AND request_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
	$retval0 = mysqli_query( $conn, $sql0 );
	$row0 = mysqli_fetch_assoc($retval0);
	if(($row0["total"] > 0) && ($userobj["whitelisted"] != "true")){die("That song has already been requested recently!");}
	
        $sql = "INSERT INTO sm_requests (song_id, request_time, requestor, twitch_tier, broadcaster, request_type) VALUES ('{$song_id}', NOW(), '{$requestor}', '{$tier}', '{$broadcaster}', 'normal')";
        $retval = mysqli_query( $conn, $sql );
}

   $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
   if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$user = $_GET["user"];
$tier = $_GET["tier"];
if(isset($_GET["userid"])){
	$twitchid = $_GET["userid"];
}else{
	$twitchid = "";
}

//get broadcaster
if(isset($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
}else{
	$broadcaster = "";
}

$userobj = check_user($twitchid, $user);

if(isset($_GET["cancel"])){
	
	if (!empty($_GET["cancel"]) && is_numeric($_GET["cancel"])){
		$num = $_GET["cancel"];
	}else{
		$num = 0;
	}

        $sql = "SELECT * FROM sm_requests WHERE requestor = '{$user}' AND state <> 'canceled' AND state <> 'skipped' ORDER BY request_time DESC LIMIT 1 OFFSET {$num}";
	$retval = mysqli_query( $conn, $sql );

        if (mysqli_num_rows($retval) == 1) {
                while($row = mysqli_fetch_assoc($retval)) {

			$request_id = $row["id"];
			$song_id = $row["song_id"];
			
            $sql2 = "SELECT * FROM sm_songs WHERE id = '{$song_id}' LIMIT 1";
            $retval2 = mysqli_query( $conn, $sql2 );
			while($row2 = mysqli_fetch_assoc($retval2)){
		        $sql3 = "UPDATE sm_requests SET state = 'canceled' WHERE id = '{$request_id}'";
        		$retval3 = mysqli_query( $conn, $sql3 );
				echo "Canceled {$user}'s request for ".trim($row2["title"]." ".$row2["subtitle"]);
			}
		}

	}else{
		echo "$user hasn't requested any songs!";
	}

die();
}

if(isset($_GET["skip"])){
	
	if(strtolower($user) !== $broadcaster){die("That's gonna be a no from me, dawg.");}

	if (!empty($_GET["skip"]) && is_numeric($_GET["skip"])){
		$num = $_GET["skip"];
	}else{
		$num = 0;
	}

	$sql = "SELECT * FROM sm_requests WHERE state <> \"canceled\" AND state <> \"skipped\" ORDER BY request_time DESC LIMIT 1 OFFSET {$num}";
        $retval = mysqli_query( $conn, $sql );

                while($row = mysqli_fetch_assoc($retval)) {
					$request_id = $row["id"];
					$song_id = $row["song_id"];
					$sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\" LIMIT 1";
					$retval2 = mysqli_query( $conn, $sql2 );
					while($row2 = mysqli_fetch_assoc($retval2)){
						$sql3 = "UPDATE sm_requests SET state=\"skipped\" WHERE id = \"$request_id\"";
						$retval3 = mysqli_query( $conn, $sql3 );
						echo "$user skipped ".trim($row2["title"]." ".$row2["subtitle"]);
					}
                }

die();
}

if(isset($_GET["songid"])){
	$song = $_GET["songid"];
        //lookup by ID and request it

        $sql = "SELECT * FROM sm_songs WHERE id = '{$song}' AND installed=1 ORDER BY title ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
    		while($row = mysqli_fetch_assoc($retval)) {
        		request_song($song, $user, $tier, $twitchid, $broadcaster);
        		echo "$user requested " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"];
        		die();
    		}
	} else {
        	echo "Didn't find any songs matching that id!";
        	die();
}

die();
}

if(isset($_GET["song"])){
	$song = $_GET["song"];
	$song = clean($song);

	//Determine if there's a song with this exact title. If someone requested "Tsugaru", this would match "TSUGARU" but would not match "TSUGARU (Apple Mix)"
        $sql = "SELECT * FROM sm_songs WHERE strippedtitle=\"$song\" AND installed=1 ORDER BY title ASC, pack ASC";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) == 1) {
		while($row = mysqli_fetch_assoc($retval)) {
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "$user requested " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"];
    		}
	die();
	//end exact match
	}

        $sql = "SELECT * FROM sm_songs WHERE strippedtitle LIKE \"%$song%\" AND installed=1 ORDER BY title ASC, pack ASC";
        $retval = mysqli_query( $conn, $sql );

if (mysqli_num_rows($retval) == 1) {
    while($row = mysqli_fetch_assoc($retval)) {
	request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        echo "$user requested " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"];
    }
die();
//end one match
}
//no one match
if (mysqli_num_rows($retval) > 0) {
	echo "Top matches (request with !requestid [song id]):\n";
	$i=1;
    while($row = mysqli_fetch_assoc($retval)) {
        if($i>4){die();}
	echo "[ ".$row["id"]. " > " .trim($row["title"]." ".$row["subtitle"])." from ".$row["pack"]." ]";
	$i++;
    }
} elseif (is_numeric($song)) {
	echo "Did you mean to use !requestid $song?";
}else{
	echo "Didn't find any songs matching that name! Check the !songlist.";
}

die();
}

?>