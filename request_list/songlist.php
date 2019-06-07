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
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
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
<p>These are all of the packs I have installed. Click the name of a pack to show/hide its songs. The number before each song title is its ID, you can request by "requestid [id number]".</p>
</div>
<?php

define('dbhost', 'localhost');
define('dbuser', 'davelingercom');
define('dbpass', '$Peed2ng');
define('db', 'davelingercom');

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

echo "</ul>\n";

?>
</body>
</html>
