<?php

require ("config.php");

if(!isset($_GET["security_key"]) || $_GET["security_key"] != $security_key){
        die("Fuck off");
}

if(!isset($_GET["security_key"])){
	die("Nope");
}

if($_GET["security_key"] != $security_key){
	die("Nope");
}

   $conn = mysqli_connect(dbhost, dbuser, dbpass, db);
   if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}
   
function format_pack($pack){
	$pack = str_ireplace("Dance Dance Revolution","DDR",$pack);
	$pack = str_ireplace("Dancing Stage","DS",$pack);
	$pack = str_ireplace("In The Groove","ITG",$pack);
	$pack = str_ireplace("Ben Speirs'","BS'",$pack);
	$pack = str_ireplace("JBEAN Exclusives","JBEAN...",$pack);
	$pack = preg_replace("/(\(.*\).\(.*\))$/","",$pack,1);
	if(strlen($pack) > 25)
		{$pack = trim(substr($pack,0,18))."...".trim(substr($pack,strlen($pack)-7));
	}
return $pack;
}   

if(!empty($_GET["broadcaster"])){
	$broadcaster = $_GET["broadcaster"];
}else{
	$broadcaster = "%";
}

if(!isset($_GET["middle"])){

echo '<html>
<head>
<link rel="stylesheet" href="style.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
function new_request(array){
	request_id = array.id;
	song_id = array.song_id;
	requestor = array.requestor;
	request_time = array.request_time;
	request_type = array.request_type;
	if (array.request_type=="random"){request_type = "<img src=\"images/d205.png\" class=\"type\">";}
	title = array.title;
	subtitle = array.subtitle;
	artist = array.artist;
	pack = array.pack;
	img = array.img;

	console.log("Adding request "+request_id);

	data = \'<div class=\"songrow\" style=\"display:none\" id=\"request_\'+request_id+\'"\">\n<h2>\'+title+\'<h2a>\'+subtitle+\'</h2a></h2>\n<h3>\'+pack+\'</h3>\n<h4>\'+requestor+\'</h4>\n\'+request_type+\'\n<img class=\"songrow-bg\" src=\"\'+img+\'\" />\n</div>\n\';

        $("#lastid").html(request_id);
        $("#middle").prepend(data);
        $("#request_"+request_id).slideDown(600);
        $("#request_"+request_id).first().css("opacity", "0");
        $("#request_"+request_id).first().css("animation", "wiggle 1.5s forwards");
        $("#new")[0].play();

}

function new_cancel(id){
	request_id = id;
	if( $("#request_"+request_id).length ){
        	console.log("Canceling request "+request_id);
        	$("#request_"+request_id).slideUp(600, function() {this.remove(); });
        	$("#cancel")[0].play();
	}
}

function completion(id){
        request_id = id;
	if( $("#request_"+request_id).length ){
		if( $("#request_"+request_id).hasClass("completed") ){
		}else{
                        console.log("Completing request "+request_id);
                        $("#request_"+request_id).removeAttr("style");
                        $("#request_"+request_id).addClass("completed");
			$("#request_"+request_id).append("<img src=\"images/check.png\" class=\"check\" />");
		}
	}
}

function skipped(id){
        request_id = id;
	if( $("#request_"+request_id).length ){
        	console.log("Skipping request "+request_id);
        	$("#request_"+request_id).slideUp(600, function() {this.remove(); });
        	$("#cancel")[0].play();
	}
}

function refresh_data(){
lastid = $("#lastid").html();
url = "get_updates.php?security_key='.$security_key.'&broadcaster='.urlencode($broadcaster).'&id="+lastid;
    $.ajax({url: url, success: function(result){
		if(result){
			result = JSON.parse(result);
			if(result["requests"].length > 0){
				howmany = result["requests"].length;
				console.log(howmany+" new request(s)");
                                $.each(result["requests"], function( key, value ) {
                                	new_request(value);
				});
			}else{
				console.log("No new requests");
			}
                        if(result["cancels"].length > 0){
                                $.each(result["cancels"], function( key, value ) {
                                        new_cancel(value);
                                });
                        }
                        if(result["completions"].length > 0){
                                $.each(result["completions"], function( key, value ) {
                                        completion(value);
                                });
						}
						if(result["skips"].length > 0){
                                $.each(result["skips"], function( key, value ) {
                                        skipped(value);
                                }); 
                        }

		}else{
			console.log("Json error downloading data");
		}
    }});
}

window.setInterval(function(){
	refresh_data();
}, 5000);    

$(function() {refresh_data();});
</script>
</head>
<body>
<audio id="new" src="new.mp3" type="audio/mpeg"></audio>
<audio id="cancel" src="cancel.mp3" type="audio/mpeg"></audio>
<div id="middle">

';

}

        //$sql = "SELECT * FROM sm_requests WHERE state=\"requested\" OR state=\"completed\" ORDER BY request_time DESC LIMIT 10";
        $sql = "SELECT * FROM sm_requests WHERE ((state=\"requested\" OR state=\"completed\") AND broadcaster LIKE \"{$broadcaster}\") ORDER BY request_time DESC LIMIT 10";
        $retval = mysqli_query( $conn, $sql );
		  $i=0;

    while($row = mysqli_fetch_assoc($retval)) {

	$request_id = $row["id"];
	$song_id = $row["song_id"];
	$request_time = $row["request_time"];
	$requestor = $row["requestor"];
	$request_type = $row["request_type"];
	if ($request_type == "random"){
			$request_type = '<img src="images/d205.png" class="type">';
		}else{
			$request_type = "";
		}
	
	if($i == 0){
		echo "<span id=\"lastid\" style=\"display:none;\">$request_id</span>\n\n";
	}

	$sql2 = "SELECT * FROM sm_songs WHERE id=\"$song_id\" LIMIT 1";
	$retval2 = mysqli_query( $conn, $sql2 );
	    while($row2 = mysqli_fetch_assoc($retval2)) {
		$title = $row2["title"];
		$subtitle = $row2["subtitle"];
		$pack = format_pack($row2["pack"]);
		$pack_img = strtolower(preg_replace('/\s+/', '_', trim($row2["pack"])));
	   }

	$pack_img = glob("images/packs/".$pack_img.".{jpg,jpeg,png,gif}", GLOB_BRACE);
	if (!$pack_img){
		$pack_img = "images/packs/unknown.png";
	}else{
		$pack_img = $pack_img[0];
	}
	
echo "<div class=\"songrow\" id=\"request_".$request_id."\">			
<h2>$title<h2a>$subtitle</h2a></h2>
<h3>$pack</h3>
<h4>$requestor</h4>
$request_type
<img class=\"songrow-bg\" src=\"{$pack_img}\" />
</div>\n";

	$i++;
    }

echo "
</div>
</html>";


?>