<?php

define('dbhost', 'localhost');
define('dbuser', 'davelingercom');
define('dbpass', '$Peed2ng');
define('db', 'davelingercom');

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

//Get new requests, cancels, and completions

function get_cancels_since($id){

	global $conn;
	//$sql = "SELECT * FROM sm_requests WHERE id > $id AND state = \"canceled\" ORDER BY id ASC";
	$sql = "SELECT * FROM sm_requests WHERE state =\"canceled\" ORDER BY id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
	$cancels = Array();
	   while($row = mysqli_fetch_assoc($retval)) {
        	$request_id = $row["id"];
		array_push($cancels, $request_id);
	}

	//$the_cancels = json_encode($cancels);

	return $cancels;

}

function get_requests_since($id){

        global $conn;
        $sql = "SELECT * FROM sm_requests WHERE id > $id AND state = \"requested\" ORDER by id ASC";
        $retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
        $requests = Array();
           while($row = mysqli_fetch_assoc($retval)) {
                
		$request_id = $row["id"];
		$requestor = $row["requestor"];
		$song_id = $row["song_id"];
		$request_time = $row["request_time"];
		
	        $sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\"";
        	$retval2 = mysqli_query( $conn, $sql2 ) or die(mysqli_error($conn2));
           		while($row2 = mysqli_fetch_assoc($retval2)) {
				$request["id"] = $request_id;
				$request["song_id"] = $song_id;
				$request["requestor"] = $requestor;
				$request["request_time"] = $request_time;
		                $request["title"] = $row2["title"];
                		$request["artist"] = $row2["artist"];
                		$request["pack"] = $row2["pack"];
				$request["img"] = "images/packs/unknown.png";
				if(file_exists("images/packs/".$request["pack"].".png")){ $request["img"] = "images/packs/".$request["pack"].".png";}
				if(file_exists("images/packs/".$request["pack"].".jpg")){ $request["img"] = "images/packs/".$request["pack"].".jpg";}
			}

                array_push($requests, $request);
        }

        //$the_requests = json_encode($requests);

        return $requests;

}

function get_completions_since($id){

        global $conn;
	$id=$id-10;
        $sql = "SELECT DISTINCT requestid FROM sm_songsplayed WHERE requestid > $id";
        $retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
        $completions = Array();
           while($row = mysqli_fetch_assoc($retval)) {
                $request_id = $row["requestid"];
                array_push($completions, $request_id);
        }

        //$the_completions = json_encode($completions);

        return $completions;

}

if(!isset($_GET["id"])){die("You must specify an id");}

$id = $_GET["id"];

$cancels = get_cancels_since($id);

$requests = get_requests_since($id);

$completions = get_completions_since($id);

$output["cancels"] = $cancels;
$output["requests"] = $requests;
$output["completions"] = $completions;

$output = json_encode($output);

echo "$output";

?>
