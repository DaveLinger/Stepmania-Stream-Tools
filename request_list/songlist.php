<html>
<head>
 <title>SM5 Songlist</title>
<link rel="stylesheet" 
	href="w3.css">
<link rel="stylesheet" 
	href="w3-theme-dark-grey.css">
<link rel="icon" 
      type="image/png" 
      href="images/ddr_arrow.png">
<style>
	body {
		background-image: url("images/extreme_bg.jpg");
		background-color:#303030;
	}
</style>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-XXXXXXXXXXXX"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'UA-XXXXXXXXXX');
	</script>
</head>

<body>
<div class="w3-container w3-theme-dark">

<center><h1><a href="songlist.php"><img src="images/ddr_arrow.png" align="float:left" width="35px" style="margin:5px"></a><strong>SM5 Songlist</strong></h1>
</center>

<?php

//

//THIS FILE IS A MESS. I WELCOME ANYONE TO IMPROVE IT.

//

include("config.php");

//create connection
$conn = mysqli_connect(dbhost, dbuser, dbpass, db);
if(! $conn ) {die('Could not connect: ' . mysqli_error($conn));}

function escape_string($str){
	global $conn;
	$str = htmlspecialchars($str);
	$str = mysqli_real_escape_string($conn, $str);
return $str;	
}

//if no variables are set, order table by random to encourage song discovery
if(!isset($_GET['order']) && !isset($_GET['query']) && !isset($_GET['sort']) && !isset($_GET['pack'])){
	$order = 'RAND()';
	$query = "";
	$pack = "";
	$sort = 'ASC';
	//$_GET['random'] = "Random";
}else{

	//query get setup
	if(isset($_GET['query'])){
		$query = escape_string($_GET['query']);
	}else{
		$query = "";
	}

	//setup order and sorts GET	
	if(isset($_GET['order'])){
		$order = escape_string($_GET['order']);
	}else{
		$order = 'PACK';
	}

	if(isset($_GET['sort'])){
		$sort = escape_string($_GET['sort']);
	}else{
		$sort = 'ASC';
	}
	
	//get selected pack from dropdown
	if(isset($_GET['pack']) && isset($_GET['order'])){
		$pack = escape_string($_GET['pack']);
	}elseif(isset($_GET['pack']) && !isset($_GET['order'])){
		$pack = escape_string($_GET['pack']);
		$order = 'TITLE';
	}else{
		$pack = "";
	}
}

//pagination setup
if(isset($_GET['pageno'])){
	$pageno = escape_string($_GET['pageno']);
}else{
	$pageno = 1;
}

$no_of_records_per_page = 50;
$offset = ($pageno-1) * $no_of_records_per_page;

//was the random button clicked?		
if(isset($_GET['random'])){
	$order = "RAND()";
}else{
	$order = $order;
}

//get total songs and packs
$no_of_songs_sql = "SELECT COUNT(*) FROM sm_songs WHERE installed = 1";
$result = mysqli_query($conn, $no_of_songs_sql);
$no_of_songs = mysqli_fetch_array($result)[0];
		
$no_of_packs_sql = "SELECT COUNT(DISTINCT pack) FROM sm_songs WHERE installed = 1";
$result = mysqli_query($conn, $no_of_packs_sql);
$no_of_packs = mysqli_fetch_array($result)[0];

// output songlist statistics
echo '<center><h3>' . number_format($no_of_songs,0,0,",") . ' songs in ' . number_format($no_of_packs,0,0,",") . ' packs</h3></center>';

//show how to request a song and other commands
echo '<center><h4>To request a song, type <strong>!request [<i>songname</i>]</strong> into the chat or <strong>!requestid [<i>id</i>]</strong>, if you know the ID# of the song.<br>
Made an Oops!, use <strong>!cancel</strong> to cancel your last request.<br>
Feeling lucky, use <strong>!random</strong> for a random song or <strong>!top</strong> for a top 100 song.
</h4></center>';

//get distinct packs and # of songs from db and set as array
$packlist = array();
if(strlen($query)>0){
	$packlist_sql = "SELECT pack, COUNT(id) AS id FROM sm_songs WHERE installed = 1 AND (title LIKE '%{$query}%' OR subtitle LIKE '%{$query}%' OR artist LIKE '%{$query}%') GROUP BY pack";
}else{
	$packlist_sql = "SELECT pack, COUNT(id) AS id FROM sm_songs WHERE installed = 1 GROUP BY pack";
}
$result = mysqli_query($conn, $packlist_sql);
while( $row = mysqli_fetch_assoc($result)){
	$packlist = array_merge($packlist, array($row['pack'] => $row['id']));
}

//show input field for searching database
echo '<center><div class="w3-center w3-input w3-border w3-light-grey" style="width:60%">
		<form method="GET">
		<input type="TEXT" style="width:100%" name="query" value="';
echo isset($_GET['query']) ? $_GET['query'] : "";
echo '" placeholder="Input a song title or artist" autofocus="AutoFocus"/>';
echo  '<select name="pack" id="pack" style="width:100%" class="w3-input w3-padding-small w3-border">
		<option value="none" selected disabled>Select a pack...</option>';
		foreach( $packlist as $key=>$value){
			echo '<option value="'.$key.'"';
			if (isset($_GET['pack']) && $_GET['pack'] == $key){echo ' selected';}
			echo '>'.$key.' ['.$value.']</option>';
		}
echo '</select>';
echo '<input type="SUBMIT" value="Search" class="w3-btn w3-border"/>
	  <input type="SUBMIT" name="random" value="Random" class="w3-btn w3-border"/>
	  <a href="songlist.php">Reset</a>';
echo '</form>';
//$pack_img = strtolower(preg_replace('/\s+/','_',trim($pack)));
//if(strlen($pack)>0 && file_exists("images/packs/".$pack_img.".png")){
//	$pack_img = "images/packs/".$pack_img.".png";
	//$pack_img = file_exists("images/packs/".$pack_img.".jpg");
//	echo '<img src="'.$pack_img.'">';
//}
echo '</div></center>';

//substitute spaces for % to widen the query results
$query = str_replace(" ","%",$query);

//get most recent date from database
$max_date_sql = mysqli_query($conn,"SELECT DATE(MAX(added)) AS max_date FROM sm_songs WHERE installed = 1");
$row = mysqli_fetch_array($max_date_sql);
$updated_date = $row["max_date"];

//find total number of pages of rows
$total_pages_sql = "SELECT COUNT(*) FROM sm_songs WHERE installed = 1 and ((title LIKE '%{$query}%' OR subtitle LIKE '%{$query}%' OR artist LIKE '%{$query}%') AND (pack LIKE '%{$pack}'))";
$result = mysqli_query($conn, $total_pages_sql);
$total_rows = mysqli_fetch_array($result)[0];
$total_pages = ceil($total_rows / $no_of_records_per_page);

//build mysql query as a string
$base_sql = "SELECT sm_songs.id,trim(concat(title,' ',subtitle,IF(bga=1,'  [V]',''))),artist,pack,sec_to_time(music_length) AS LENGTH,IF(sm_songs.display_bpm>0,sm_songs.display_bpm,NULL) AS BPM,  
sum(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.meter END) AS meter_BSP, 
sum(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.meter END) AS meter_ESP, 
sum(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.meter END) AS meter_MSP, 
sum(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.meter END) AS meter_HSP, 
sum(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.meter END) AS meter_CSP, 
sum(case when sm_notedata.stepstype LIKE 'dance-single' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.meter END) AS meter_XSP, 
sum(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Beginner' then sm_notedata.meter END) AS meter_BDP, 
sum(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Easy' then sm_notedata.meter END) AS meter_EDP, 
sum(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Medium' then sm_notedata.meter END) AS meter_MDP, 
sum(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Hard' then sm_notedata.meter END) AS meter_HDP, 
sum(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Challenge' then sm_notedata.meter END) AS meter_CDP, 
sum(case when sm_notedata.stepstype LIKE 'dance-double' AND sm_notedata.difficulty LIKE 'Edit' then sm_notedata.meter END) AS meter_XDP
FROM sm_songs
JOIN sm_notedata ON sm_songs.id=sm_notedata.song_id
WHERE stepstype NOT LIKE 'lights-cabinet' AND sm_songs.installed = 1 AND (
				(title LIKE '%{$query}%' OR subtitle LIKE '%{$query}%' OR artist LIKE '%{$query}%') 
				AND (pack LIKE '%{$pack}')
				) 
GROUP BY sm_songs.id 
ORDER BY {$order} {$sort} LIMIT {$offset}, {$no_of_records_per_page}";
			
$result = mysqli_query($conn, $base_sql);
$all_property = array();  //declare an array for saving property

//undo query % formatting
$query = str_replace("%"," ",$query);

//toggle sorts
$sortp = $sort;
$pack = stripslashes($pack);
$query = stripslashes($query);

//show summary of results above table
if (strlen($query)<1 && strlen($pack)<1){
		echo '<div class="w3padding-small"><h3>All songs sorted ';
			if($order == 'RAND()') {
			echo 'randomly'; 
			}else{ echo 'by '.$order.'';
			}
		echo ':</h3></div>';
	}else{
		echo '<div class="w3padding-small"><h3>Found ' .number_format($total_rows,0,0,",").' song(s) ';
		if(strlen($query)>0){
			echo 'for "'.$query.'"';
		}
			if(strlen($pack)>0){
			echo ' in '.$pack;
			}
			echo ' sorted ';
			if($order == 'RAND()') {
				echo 'randomly'; 
				}else{ echo 'by '.$order.'';
				}
		
			echo ':</h3></div>';
	}

//showing property
echo '<table class="w3-table-all w3-margin-top" id="myTable">
	<colgroup>
	<col style="width: 2%">
	<col style="width: 27%">
	<col style="width: 27%">
	<col style="width: 27%">
	<col style="width: 5%">
	<col style="width: 2%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	<col style="width: 1%">
	</colgroup>
	<tbody>
      <tr class="data-heading">';
echo '<th class="w3-center"><a href="?query=' . $query . '&pack=' . $pack . '&order=ID&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">ID</a></th>';
echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=TITLE&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">TITLE</a></th>';
echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=ARTIST&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">ARTIST</a></th>';
echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=PACK&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">PACK</a></th>';
echo '<th class="w3-center"><a href="?query=' . $query . '&pack=' . $pack . '&order=LENGTH&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">LENGTH</a></th>';
echo '<th class="w3-center"><a href="?query=' . $query . '&pack=' . $pack . '&order=BPM&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '">BPM</a></th>';
echo '<th colspan="6" class="w3-center">SINGLE</th>';
echo '<th colspan="6" class="w3-center">DOUBLE</th>';
echo '</tr>';
echo '<tr>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_bsp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_beginner.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_esp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_light.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_msp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_standard.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_hsp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_heavy.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_csp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_challenge.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_xsp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_edit.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_bdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_beginner.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_edp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_light.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_mdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_standard.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_hdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_heavy.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_cdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_challenge.gif"></a></th>
	<th class="w3-center" style="padding: 2px 4px"><a href="?query=' . $query . '&pack=' . $pack . '&order=meter_xdp&sort='; if($sort=='DESC'){echo 'ASC';}else{echo 'DESC';} echo '"><img src="images/icon_edit.gif"></a></th>';
echo '</tr>';

while ($property = mysqli_fetch_field($result)) {
//	if($order == $property->name) {
//		$sort == 'DESC' ? $sort = 'ASC' : $sort = 'DESC';
//	}else{ $sort = 'ASC';
//	}
//	echo '<th><a href="?query=' . $query . '&pack=' . $pack . '&order=' . $property->name . '&sort=' . $sort . '">' . $property->name . '</a></th>';  //get field name for header
array_push($all_property, $property->name);  //save those to array
}
//echo '</tr>'; //end tr tag

//showing all data
while ($row = mysqli_fetch_array($result)) {
    echo "<tr>";
    foreach ($all_property as $item) {
        echo '<td>' . $row[$item] . '</td>'; //get items using property value
    }
    echo '</tr>';
}
echo "</tbody></table>";
?>

<div class="w3-left">
	Songlist last updated: <?php echo $updated_date; ?>
</div>

<div class="w3-right">	
	Records <?php echo number_format(($pageno*$no_of_records_per_page)-$no_of_records_per_page+1,0,0,','); ?>-<?php if($pageno==$total_pages){echo number_format($total_rows,0,0,','); } else { echo number_format($pageno*$no_of_records_per_page,0,0,',');} ?> of 
	<?php echo number_format($total_rows,0,0,','); ?>
</div>

<div class="w3-bar w3-border w3-round">
  <a href="<?php { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=1"; } ?>" class="w3-button">&laquo; First</a>
  
  <a href="<?php if($pageno <= 1){ echo '#'; } else { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=".($pageno - 1); } ?>" class="w3-button">&#10094; Previous</a>
  
  <a href="<?php { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=".$total_pages; } ?>" class="w3-button w3-right">Last &raquo;</a>
  
  <a href="<?php if($pageno >= $total_pages){ echo '#'; } else { echo "?query=".$query."&pack=".$pack."&order=".$order."&sort=".$sortp."&pageno=".($pageno + 1); } ?>" class="w3-button w3-right">Next &#10095;</a>
</div>
</div>

<div class="w3-padding-small w3-container w3-theme w3-center">
StepMania song scraping code used to populate this table <strike>stolen</strike> borrowed from <a href="https://github.com/DaveLinger/Stepmania-Stream-Tools" target="_blank">Dave Linger</a> aka <a href="https://twitch.tv/ddrdave" target="_blank">(ddrDave)</a>.
</div>

</html>
</body>