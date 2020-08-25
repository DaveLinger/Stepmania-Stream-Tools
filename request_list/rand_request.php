<?php
   
include("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
    die("Fuck off");
}
//limit to how many random songs can be requested at once
$max_num = 3;

function clean($string) {
   global $conn;
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
   $string = mysqli_real_escape_string($conn, $string); // Removes sql injection atempts.
}

if(!isset($_GET["user"])){
	die("Error");
}

if(!isset($_GET["random"]) && !isset($_GET["num"]) && !is_numeric($_GET["num"])){
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

	//check total length of requests, if over 10, stop
	$length = check_length();
	
	if($length > 10){
		die("Too many songs on the request list! Try again in a few minutes.");
	}
    $interval = 0.5 * $length;
	
	//scale cooldown as a function of the number of requests. 30 seconds per open request.	
		global $conn;
        $sql0 = "SELECT * FROM sm_requests WHERE state <> \"canceled\" AND requestor = \"$user\" AND request_time > DATE_SUB(NOW(), INTERVAL {$interval} MINUTE)";
        $retval0 = mysqli_query( $conn, $sql0 );
	$numrows = mysqli_num_rows($retval0);
	if($numrows != 0){
		die("Slow down there, part'ner! Try again in ".ceil($interval)." minutes");
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

	$sql0 = "SELECT COUNT(*) AS total FROM sm_requests WHERE song_id = \"$song_id\" AND state <> 'canceled' AND request_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
	$retval0 = mysqli_query( $conn, $sql0 );
	$row0 = mysqli_fetch_assoc($retval0);
	if(($row0["total"] > 0) && ($userobj["whitelisted"] != "true")){die("That song has already been requested recently!");}

        $sql = "INSERT INTO sm_requests (song_id, request_time, requestor, twitch_tier, broadcaster, request_type) VALUES ('{$song_id}', NOW(), '{$requestor}', '{$tier}', '{$broadcaster}', 'random')";
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
//get broadcaster and adjust query filters
if(isset($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
	if (array_key_exists($broadcaster,$broadcasters)){
		$profileName = $broadcasters[$broadcaster];
	}else{
		$profileName = "%";
	}
}else{
	$broadcaster = "";
	$profileName = "%";
}

//get number of random requests, if not specified, set as 1
if (isset($_GET["num"]) && is_numeric($_GET["num"])){
	$num = $_GET["num"];
}else{ $num = 1;}

if($num > $max_num){
	die("$user can't request that many songs at once!");
}

//standard random request from songs that have at least been played once
if($_GET["random"]=="random"){

        //$sql = "SELECT * FROM sm_songs WHERE installed=1 AND banned IS NULL ORDER BY RAND() LIMIT {$num}";
        $sql = "SELECT DISTINCT (sm_songs.id) AS id,sm_songs.title AS title,sm_songs.subtitle AS subtitle,sm_songs.artist AS artist,sm_songs.pack AS pack 
		FROM sm_songs 
		JOIN sm_scores ON sm_scores.song_id=sm_songs.id 
		WHERE song_id > 0 AND username LIKE \"{$profileName}\" AND banned<>1 AND installed=1 
		ORDER BY RAND() 
		LIMIT {$num}";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while($row = mysqli_fetch_assoc($retval)) {
				if($i>$num || $i>$max_num){die();}
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "{$user} randomly requested " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"] . " ";
				$i++;
			}
	} else {
        	die("Didn't find any random songs!");
}

die();
}

//standard portal request, any installed/unbanned songs can be selected
if($_GET["random"]=="portal"){

        $sql = "SELECT * FROM sm_songs WHERE installed=1 AND banned<>1 ORDER BY RAND() LIMIT {$num}";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while($row = mysqli_fetch_assoc($retval)) {
				if($i>$num || $i>$max_num){die();}
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "{$user} opened a portal to " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"] . " ";
				$i++;
			}
	} else {
        	die("Didn't find any portal songs!");
}

die();
}

//standard top request of 1 random 100 most played songs
if($_GET["random"]=="top"){

        $sql = "SELECT id,title,subtitle,artist,pack,numplayed
				FROM sm_songs 
				JOIN 
					(SELECT song_id,MAX(numplayed) AS numplayed
					FROM sm_scores
					WHERE song_id>0 AND username LIKE \"{$profileName}\" 
					GROUP BY song_id
					ORDER BY numplayed desc
					LIMIT 100) AS t2
				ON t2.song_id=sm_songs.id 
				WHERE banned<>1 AND installed=1 AND numplayed >1 
				ORDER BY RAND()
				LIMIT {$num}";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while($row = mysqli_fetch_assoc($retval)) {
				if($i>$num || $i>$max_num){die();}
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "{$user} picked a top request for " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"] . " ";
				$i++;
			}
	} else {
        	die("Didn't find any top songs!");
}

die();
}

//random worst 20 scored top 100 songs
if($_GET["random"]=="gitgud"){

        $sql = "SELECT id,title,subtitle,artist,pack,numplayed,percentdp 
				FROM sm_songs 
				JOIN 
				(SELECT song_id,numplayed,MAX(percentdp) AS percentdp
					FROM sm_scores 
					WHERE EXISTS 
						(SELECT song_id,numplayed,percentdp  
						FROM sm_scores 
						WHERE song_id>0 AND username LIKE \"{$profileName}\"  
						GROUP BY song_id
						ORDER BY numplayed DESC 
						LIMIT 100) 
					AND grade <> 'Failed' AND numplayed > 1 AND percentdp > 0 
					GROUP BY song_id 
					ORDER BY percentdp ASC 
					LIMIT 25) AS t2 
				ON t2.song_id = sm_songs.id 
				WHERE banned <> 1 AND installed = 1 
				ORDER BY RAND()
				LIMIT {$num}";
        $retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while($row = mysqli_fetch_assoc($retval)) {
				if($i>$num || $i>$max_num){die();}
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "{$user} dares you to beat ".number_format($row['percentdp']*100,2)."% at " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"] . " ";
				$i++;
			}
	} else {
        	die("Didn't find any songs to git gud at!");
}

die();
}

//edge-case random request just for djfipu
if($_GET["random"]=="djfipu"){
		
		$random = $_GET["random"];
		$random = htmlspecialchars($random);
		//$random = clean($random);
		
        $sql = "SELECT * FROM sm_songs WHERE installed=1 AND banned<>1 AND (artist IN('e-rotic','lollipop','crispy','aqua','missing heart') OR title IN('exotic ethnic','Dadadadadadadadadada','Bi') OR title LIKE '%euro%' OR subtitle LIKE '%euro%' OR pack IN('simfile.pl')) ORDER BY RAND() LIMIT {$num}";
		$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while($row = mysqli_fetch_assoc($retval)) {
				if($i>$num || $i>$max_num){die();}
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "{$user} requested @djfipu's favorite song: " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"] . " ";
				$i++;
			}
	} else {
        	die("@djfipu, what's this all about!?");
}

die();
}

//specific pack(s) random request/catch-all REGEX pack name matching
//randomben, randomddr, randomhellkite...
if($_GET["random"]!=="random"){
		
		$random = $_GET["random"];
		$random = htmlspecialchars($random);
		//$random = clean($random);
		
        $sql = "SELECT * FROM sm_songs WHERE installed=1 AND banned<>1 AND pack REGEXP \"{$random}\" ORDER BY RAND() LIMIT {$num}";
		$retval = mysqli_query( $conn, $sql );

	if (mysqli_num_rows($retval) > 0) {
			$i=1;
    		while($row = mysqli_fetch_assoc($retval)) {
				if($i>$num || $i>$max_num){die();}
        		request_song($row["id"], $user, $tier, $twitchid, $broadcaster);
        		echo "{$user} randomly requested " . trim($row["title"]." ".$row["subtitle"]). " from " . $row["pack"] . " ";
				$i++;
			}
	} else {
        	die("Uh oh. RNGesus was not on your side!");
}

die();
}

die();

?>
