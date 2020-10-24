<?php
//
//Contains all functions for managing users during request processing and other misc. functions that are called throughout
//

//include("config.php");

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function add_user($userid, $user){

	global $conn;
	
	$user = strtolower($user);
	$sql = "INSERT INTO sm_requestors (twitchid, name, dateadded) VALUES (\"$userid\", \"$user\", NOW())";
	$retval = mysqli_query( $conn, $sql );
	$the_id = mysqli_insert_id($conn);

	return($the_id);

}

function check_user($userid, $user){

    global $conn;

    $user = strtolower($user);

    //case where the bot cannot supply the twitchid, use the name
    if($userid > 0 || !empty($userid)){
        $sql0 = "SELECT * FROM sm_requestors WHERE twitchid = \"$userid\"";
    }else{
        $sql0 = "SELECT * FROM sm_requestors WHERE name = \"$user\"";
    }
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

    //check total length of requests, if over 10, stop
    $length = check_length();

    if($length > 10){
        die("Too many songs on the request list! Try again in a few minutes.");
    }
    $interval = 0.75 * $length;

    //scale cooldown as a function of the number of requests. 45 seconds per open request.	
    global $conn;
    $sql0 = "SELECT * FROM sm_requests WHERE state <> \"canceled\" AND requestor = \"$user\" AND request_time > DATE_SUB(NOW(), INTERVAL {$interval} MINUTE)";
    $retval0 = mysqli_query( $conn, $sql0 );
    $numrows = mysqli_num_rows($retval0);
    if($numrows != 0){
        die("Slow down there, part'ner! Try again in ".ceil($interval)." minutes.");
    }
}

function recently_played($song_id){
	global $conn;
	$recently_played = FALSE;
	$sql = "SELECT song_id FROM sm_songsplayed WHERE song_id={$song_id} AND lastplayed > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
	$retval = mysqli_query($conn,$sql);
	if(mysqli_num_rows($retval) > 0){
		$recently_played = TRUE;
	}
	return $recently_played;
}

?>