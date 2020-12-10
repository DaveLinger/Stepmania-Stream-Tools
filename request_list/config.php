<?php

//Check for docker variables to use first

if(getenv("MYSQL_DATABASE") != ""){
    $dbname = getenv("MYSQL_DATABASE");
    define('dbhost', 'mysql');
    define('db', $dbname);
}else{
    //Your database host. 'localhost' if you are running the DB on the same machine as the web server.
    define('dbhost', '');
    //Your database name.
    define('db', '');
}

if(getenv("MYSQL_USER") != ""){
    $dbuser = getenv("MYSQL_USER");
    define('dbuser', $dbuser);
}else{
    //Your database username.
    define('dbuser', '');
}

if(getenv("MYSQL_PASSWORD") != ""){
    $dbpass = getenv("MYSQL_PASSWORD");
    define('dbpass', $dbpass);
}else{
    //Your database password.
    define('dbpass', '');
}

if(getenv("SECRET_KEY") != ""){
    $security_key = getenv("SECRET_KEY");
}else{
    //Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
    //This way people can't hit your endpoints directly without permission.
    $security_key = "";
}

if(getenv("BANNER_DIR") != ""){
    $uploaddir = getenv("BANNER_DIR");
}else{
	//Upload directory for banner pack images (absolute directory on server)
	$uploaddir = '/var/www/html/sm5/images/packs';
}

//List of games or channel categories that must be set as the "current game" on Twitch for the bot to work.
//This is used as a backup if your bot does not support per game custom commands.
$categoryGame = array('StepMania');

//Broadcaster List. Define an array to associate broadcaster names with StepMania profile names.
//This is only required if your StepMania setup is used by more than 1 twitch account.
$broadcasters = array(
						//twitch id			//SM5 profile
						'ddrdave' 		=> 	'Dave',
						'mrtwinkles47' => 	'MRT'
                     );
                     
if($security_key == ""){
    die("Security key must be set in .env file to use this software!");
}

?>
