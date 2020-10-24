<?php

include("config.php");

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function getLastRequest(){
	global $conn;
	$lastRequest = array();
	$sql = "SELECT * FROM sm_requests WHERE state <> 'canceled' ORDER BY request_time DESC LIMIT 1";
	$retval = mysqli_query( $conn, $sql );
	$lastRequest = mysqli_fetch_assoc($retval);
	
	return $lastRequest;
}

if($_GET["data"] == "requests"){
	
$timestamp = getLastRequest()['request_time'];

$sql = "SELECT COUNT(*) AS requestsToday FROM sm_requests WHERE state <> 'canceled' AND request_time > date_sub(\"{$timestamp}\", interval 3 hour)";
$retval = mysqli_query( $conn, $sql );

$row = mysqli_fetch_assoc($retval);
$requestsToday = $row["requestsToday"];

echo "$requestsToday &nbsp; requests this session";

}

if($_GET["data"] == "songs"){

///

$timestamp = getLastRequest()['request_time'];

$sql = "SELECT COUNT(DISTINCT datetime) AS playedToday FROM sm_scores WHERE datetime > date_sub(\"{$timestamp}\", interval 3 hour)";
$retval = mysqli_query( $conn, $sql );

$row = mysqli_fetch_assoc($retval);
$playedToday = $row["playedToday"];

echo "$playedToday &nbsp; songs played this session";

}

if($_GET["data"] == "scores"){

///

$timestamp = getLastRequest()['request_time'];

$sql = "SELECT sm_grade_tiers.itg_grade,FORMAT(AVG(sm_scores.percentdp*100),2) AS percentdp,COUNT(sm_scores.grade) AS gradeCount 
FROM sm_scores 
LEFT JOIN sm_grade_tiers ON sm_grade_tiers.itg_tier = sm_scores.grade
WHERE sm_scores.datetime > date_sub(\"{$timestamp}\", interval 3 hour) AND sm_scores.grade <> 'Failed' 
GROUP BY sm_scores.grade 
ORDER BY sm_scores.grade ASC";
mysqli_set_charset($conn,"utf8mb4");
$retval = mysqli_query( $conn, $sql );

while ($row = mysqli_fetch_assoc($retval)){
	echo $row['itg_grade']." (".$row['percentdp'].") - ".$row['gradeCount']."</br>";

}

}

if($_GET["data"] == "recent"){

///

$timestamp = getLastRequest()['request_time'];

$sql = "SELECT TRIM(CONCAT(sm_songs.title,' ',sm_songs.subtitle)) AS title,sm_songs.pack AS pack,sm_grade_tiers.itg_grade,FORMAT(sm_scores.percentdp*100,2) AS percentdp 
FROM sm_scores 
JOIN sm_grade_tiers ON sm_grade_tiers.itg_tier = sm_scores.grade 
JOIN sm_songs ON sm_songs.id = sm_scores.song_id 
WHERE sm_scores.datetime > date_sub(\"{$timestamp}\", interval 3 hour) AND sm_scores.grade <> 'Failed' 
ORDER BY sm_scores.datetime DESC 
LIMIT 5";
mysqli_set_charset($conn,"utf8mb4");
$retval = mysqli_query( $conn, $sql );

echo '<table>';
while ($row = mysqli_fetch_assoc($retval)){
	echo '<tr>';
	echo '<td>'.$row['title'].'</td><td>'.$row['pack'].'</td><td><strong>'.$row['itg_grade'].'</strong></td><td>('.$row['percentdp'].')';
	echo '</tr>';
}
echo '</table>';
}

if($_GET["data"] == "requestors"){

///

//$broadcasters = implode('","',array_keys($broadcasters));
$broadcasters = getLastRequest()['broadcaster'];
$timestamp = getLastRequest()['request_time'];

$sql = "SELECT requestor,COUNT(id) AS count FROM sm_requests WHERE state <> 'canceled' AND LOWER(requestor) NOT IN(\"{$broadcasters}\") AND request_time > date_sub(\"{$timestamp}\", interval 3 hour) GROUP BY requestor ORDER BY count DESC LIMIT 5";
$retval = mysqli_query( $conn, $sql );

echo '<h1>Special thanks to requestors:</h1>';
while ($row = mysqli_fetch_assoc($retval)){
	echo $row['requestor']."</br>";
}

}

?>
