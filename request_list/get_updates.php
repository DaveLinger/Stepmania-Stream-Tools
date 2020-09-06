<?php

require("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
        die("Fuck off");
}

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function format_pack($pack){
	$pack = str_ireplace("Dance Dance Revolution","DDR",$pack);
	$pack = str_ireplace("Dancing Stage","DS",$pack);
	$pack = str_ireplace("Ben Speirs'","BS'",$pack);
	$pack = str_ireplace("JBEAN Exclusives","JBEAN...",$pack);
	$pack = preg_replace("/(\(.*\).\(.*\))$/","",$pack,1);
	if(strlen($pack) > 25)
		{$pack = trim(substr($pack,0,18))."...".trim(substr($pack,strlen($pack)-7));
	}
return $pack;
}

//Get new requests, cancels, and completions

function get_cancels_since($id,$broadcaster){

	global $conn;
	$sql = "SELECT * FROM sm_requests WHERE state =\"canceled\" AND broadcaster LIKE \"{$broadcaster}\" ORDER BY id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
	$cancels = Array();
	   while($row = mysqli_fetch_assoc($retval)) {
        	$request_id = $row["id"];
		array_push($cancels, $request_id);
	}

	return $cancels;

}

function get_requests_since($id,$broadcaster){

        global $conn;
        $sql = "SELECT * FROM sm_requests WHERE id > $id AND state = \"requested\" AND broadcaster LIKE \"{$broadcaster}\" ORDER by id ASC";
        $retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
        $requests = Array();
           while($row = mysqli_fetch_assoc($retval)) {
                
		$request_id = $row["id"];
		$requestor = $row["requestor"];
		$song_id = $row["song_id"];
		$request_time = $row["request_time"];
		$request_type = $row["request_type"];
		if ($request_type != "random"){
			$request_type = "";
		}
		
	        $sql2 = "SELECT * FROM sm_songs WHERE id = \"$song_id\"";
        	$retval2 = mysqli_query( $conn, $sql2 ) or die(mysqli_error($conn2));
           		while($row2 = mysqli_fetch_assoc($retval2)) {
					$request["id"] = $request_id;
					$request["song_id"] = $song_id;
					$request["requestor"] = $requestor;
					$request["request_time"] = $request_time;
					$request["request_type"] = $request_type;
					$request["title"] = $row2["title"];
					$request["subtitle"] = $row2["subtitle"];
					$request["artist"] = $row2["artist"];
					$request["pack"] = format_pack($row2["pack"]);
					$pack_img = strtolower(preg_replace('/\s+/', '_', trim($row2["pack"])));
					$pack_img = glob("images/packs/".$pack_img.".{jpg,jpeg,png,gif}", GLOB_BRACE);
					if (!$pack_img){
						$request["img"] = "images/packs/unknown.png";
					}else{
						$request["img"] = $pack_img[0];
					}
				}

                array_push($requests, $request);
        }

        return $requests;

}

function get_completions_since($id,$broadcaster){

        global $conn;
		$id=$id-50;
        $sql = "SELECT id FROM sm_requests WHERE id > $id AND state = \"completed\" AND broadcaster LIKE \"{$broadcaster}\"";
        $retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
        $completions = Array();
           while($row = mysqli_fetch_assoc($retval)) {
                $request_id = $row["id"];
                array_push($completions, $request_id);
        }

        return $completions;

}

function get_skips_since($id,$broadcaster){

	global $conn;
	$sql = "SELECT * FROM sm_requests WHERE state =\"skipped\" AND broadcaster LIKE \"{$broadcaster}\" ORDER BY id ASC";
	$retval = mysqli_query( $conn, $sql ) or die(mysqli_error($conn));
	$skips = Array();
	   while($row = mysqli_fetch_assoc($retval)) {
        	$request_id = $row["id"];
		array_push($skips, $request_id);
	}

	return $skips;

}

if(!isset($_GET["id"])){die("You must specify an id");}

$id = $_GET["id"];

if(!empty($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
}else{
	$broadcaster = "%";
}

$cancels = get_cancels_since($id,$broadcaster);

$requests = get_requests_since($id,$broadcaster);

$completions = get_completions_since($id,$broadcaster);

$skips = get_skips_since($id,$broadcaster);

$output["cancels"] = $cancels;
$output["requests"] = $requests;
$output["completions"] = $completions;
$output["skips"] = $skips;

$output = json_encode($output);

echo "$output";

?>