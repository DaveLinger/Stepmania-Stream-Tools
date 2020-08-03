<?php

include("includes/config.php");

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

if($_GET["data"] == "requests"){

$sql = "SELECT COUNT(*) AS requestsToday FROM sm_requests WHERE request_time > date_sub(now(), interval 12 hour);";
$retval = mysqli_query( $conn, $sql );

$row = mysqli_fetch_assoc($retval);
$requestsToday = $row["requestsToday"];

echo "$requestsToday requests today";

}

if($_GET["data"] == "songs"){

///

$sql = "SELECT COUNT(*) AS playedToday FROM sm_songsplayed WHERE played > date_sub(now(), interval 12 hour);";
$retval = mysqli_query( $conn, $sql );

$row = mysqli_fetch_assoc($retval);
$playedToday = $row["playedToday"];

echo "$playedToday songs played today";

}

?>
