<?php

include("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
    die("Fuck off");
}

if(!isset($_GET["banuser"]) && !isset($_GET["whitelist"])){
	die();
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function clean_user($user){
	global $conn;
	$user = trim(mysqli_real_escape_string($conn,$user));
	$user = strtolower($user);
	if (strpos($user,'@') == 0){
		$user = substr($user,1);
	}
	return $user;
}

function toggle_ban($user){

    global $conn;

	$sql0 = "SELECT * FROM sm_requestors WHERE name = \"$user\"";
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0);
        if($numrows == 0){
                echo "User has to request a song before being banned, or be manually added.";
	}

        if($numrows == 1){
                $row0 = mysqli_fetch_assoc($retval0);
		$id = $row0["id"];
                $banned = $row0["banned"];
		if($banned == "true"){
			$value = "false";
			$response = "Unbanned $user. Don't be a dick.";
		}else{
			$value = "true";
			$response = "Banned $user. I'm sorry--it's for the best.";
		}

	        $sql = "UPDATE sm_requestors SET banned=\"$value\" WHERE id=\"$id\" LIMIT 1";
        	$retval = mysqli_query( $conn, $sql );

		echo "$response";

	}

}

function toggle_whitelist($user){

        global $conn;

        $sql0 = "SELECT * FROM sm_requestors WHERE name = \"$user\"";
        $retval0 = mysqli_query( $conn, $sql0 );
        $numrows = mysqli_num_rows($retval0);
        if($numrows == 0){
                echo "User has to request a song before being whitelisted, or be manually added.";
        }

        if($numrows == 1){
                $row0 = mysqli_fetch_assoc($retval0);
                $id = $row0["id"];
                $banned = $row0["whitelisted"];
                if($banned == "true"){
                        $value = "false";
                        $response = "Unwhitelisted $user. Hope you like cooldowns.";
                }else{
                        $value = "true";
                        $response = "Whitelisted $user. With great power comes great responsibility.";
                }

                $sql = "UPDATE sm_requestors SET whitelisted=\"$value\" WHERE id=\"$id\" LIMIT 1";
                $retval = mysqli_query( $conn, $sql );

                echo "$response";

        }

}

if(isset($_GET["banuser"])){
	toggle_ban(clean_user($_GET["banuser"]));
}

if(isset($_GET["whitelist"])){
    toggle_whitelist(clean_user($_GET["whitelist"]));
}

?>
