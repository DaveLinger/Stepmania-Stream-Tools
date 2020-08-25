<?php

include("config.php");

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

if($_GET["data"] == "requests"){

$sql = "SELECT COUNT(*) AS requestsToday FROM sm_requests WHERE state <> 'canceled' AND request_time > date_sub(now(), interval 6 hour)";
$retval = mysqli_query( $conn, $sql );

$row = mysqli_fetch_assoc($retval);
$requestsToday = $row["requestsToday"];

echo "$requestsToday &nbsp; requests this session";

}

if($_GET["data"] == "songs"){

///

$sql = "SELECT COUNT(DISTINCT datetime) AS playedToday FROM sm_scores WHERE datetime > date_sub(now(), interval 6 hour)";
$retval = mysqli_query( $conn, $sql );

$row = mysqli_fetch_assoc($retval);
$playedToday = $row["playedToday"];

echo "$playedToday &nbsp; songs played this session";

}

if($_GET["data"] == "scores"){

///

$sql = "SELECT sm_grade_tiers.itg_grade,FORMAT(AVG(sm_scores.percentdp*100),2) AS percentdp,COUNT(sm_scores.grade) AS gradeCount 
FROM sm_scores 
LEFT JOIN sm_grade_tiers ON sm_grade_tiers.itg_tier = sm_scores.grade
WHERE sm_scores.datetime > date_sub(now(), interval 6 hour) AND sm_scores.grade <> 'Failed' 
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

$sql = "SELECT TRIM(CONCAT(sm_songs.title,' ',sm_songs.subtitle)) AS title,sm_songs.pack AS pack,sm_grade_tiers.itg_grade,FORMAT(sm_scores.percentdp*100,2) AS percentdp 
FROM sm_scores 
JOIN sm_grade_tiers ON sm_grade_tiers.itg_tier = sm_scores.grade 
JOIN sm_songs ON sm_songs.id = sm_scores.song_id 
WHERE sm_scores.datetime > date_sub(now(), interval 6 hour) AND sm_scores.grade <> 'Failed' 
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

$sql = "SELECT requestor,COUNT(id) AS count FROM sm_requests WHERE state <> 'canceled' AND request_time > date_sub(now(), interval 6 hour) GROUP BY requestor ORDER BY count DESC";
$retval = mysqli_query( $conn, $sql );

echo 'Special thanks to requestors:</br>';
while ($row = mysqli_fetch_assoc($retval)){
	echo $row['requestor']."</br>";
}

}

?>
