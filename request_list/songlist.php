<html>
<head>

<style>
@import url('https://fonts.googleapis.com/css?family=Ubuntu');

html {

}
body {
        font-family: 'Ubuntu', sans-serif;
}
.pack {
	cursor:pointer;
	font-weight:900;
}
.song_list {
	display:none;
}
#top {
	text-align:center;
}
  label {
    display: inline-block;
    width: 5em;
  }
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
 <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script>
  $( function() {
    $( document ).tooltip();
  } );
  </script>
<script>
$(document).ready(function(){

$("li").click(function() {
        $(this).next(".song_list").toggle();
});

});
</script>

</head>
<body>

<div id="top">
<img src="images/ddrdlogo.png" style="width:50%;" /><br /><br />
<h3>Commands</h3>
<p>!request <b>songname</b><br />
<span style="color:#ccc;">!request Trip Machine Survivor = <b>TwitchUser requested Trip Machine Survivor from DDR EXTREME</b></span></p>

<p>!requestid <b>songid</b><br />
<span style="color:#ccc;">!requestid 1075 = <b>TwitchUser requested Boys from DDR 2ndMIX</b></span></p>

<p>!cancel<br />
<span style="color:#ccc;">Cancels your last request</span></p>

<p>!random<br />
<span style="color:#ccc;">Requests a random (within certain specifications) song</span></p>

<p>!top<br />
<span style="color:#ccc;">Requests a song from the top 50 most played songs on this channel</span></p>

<p></p>
<p>These are all of the packs I have installed. Click the name of a pack to show/hide its songs. The number before each song title is its ID, you can request by "requestid [id number]".</p>
</div>
<?php

include("includes/config.php");

$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

$sql = "SELECT DISTINCT pack FROM sm_songs ORDER BY pack ASC";
$retval = mysqli_query( $conn, $sql );

echo "<ul>\n";

if (mysqli_num_rows($retval) > 1) {
	while($row = mysqli_fetch_assoc($retval)) {
		$pack = $row["pack"];
		echo "<li class=\"pack\">$pack</li>\n";

		$sql2 = "SELECT * FROM sm_songs WHERE pack=\"$pack\" ORDER BY title ASC";
		$retval2 = mysqli_query( $conn, $sql2 );
		if (mysqli_num_rows($retval2) > 0) {
			echo "<div class=\"song_list\">\n";
		        while($row2 = mysqli_fetch_assoc($retval2)) {

				echo $row2["id"]." : ".$row2["title"]." by ".$row2["artist"]."<br />";

			}
			echo "</div>\n";
		}
	}
}

        $songlist = Array();

        $sql0 = "SELECT song_id, COUNT(*) occurrences FROM sm_requests WHERE state <> \"canceled\" GROUP BY song_id HAVING COUNT(*) > 1 ORDER BY COUNT(*) DESC";
        $retval0 = mysqli_query( $conn, $sql0 );

        $requeststotal = mysqli_num_rows($retval0);

        while($row0 = mysqli_fetch_assoc($retval0)) {

		$songid = $row0["song_id"];
                $sql2 = "SELECT * FROM sm_songs WHERE id=\"$songid\"";
                $retval2 = mysqli_query($conn,$sql2);
                while($row2 = mysqli_fetch_assoc($retval2)) {
                        $title = $row2["title"];
			$artist = $row2["artist"];
                }

                if(!array_key_exists($songid,$songlist)){
			$songlist[$songid]["id"] = $songid;
                        $songlist[$songid]["title"] = $title;
			$songlist[$songid]["artist"] = $artist;
                }
        }

        $sql1 = "SELECT songid, artist, title, COUNT(*) occurrences FROM sm_songsplayed WHERE songid <> 0 GROUP BY songid HAVING COUNT(*) > 2 ORDER BY COUNT(*) DESC";
        $retval1 = mysqli_query( $conn, $sql1 );

        $playedtotal = mysqli_num_rows($retval1);

        $skippedsongs = 0;
        while($row1 = mysqli_fetch_assoc($retval1)) {
                $songid = $row1["songid"];
                $title = $row1["title"];
		$artist = $row1["artist"];
                if(!array_key_exists($songid,$songlist)){
			$songlist[$songid]["id"] = $songid;
                        $songlist[$songid]["title"] = $title;
                	$songlist[$songid]["artist"] = $artist;
		}
        }

		$numbr = count($songlist);
                echo "<li class=\"pack\"><b>Songs Eligible for Random Selection ($numbr)</b></li>\n";
                echo "<div class=\"song_list\">\n";

usort($songlist, function ($item1, $item2) {
    if ($item1['title'] == $item2['title']) return 0;
    return $item1['title'] < $item2['title'] ? -1 : 1;
});

foreach($songlist as $key => $value){
	$songid = $value["id"];
	$artist = $value["artist"];
	$title = $value["title"];
	echo $songid." : ".$title." by ".$artist."<br />";
}
echo "</div>\n";
echo "</ul>\n";

?>
</body>
</html>
