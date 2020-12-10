<?php

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

//Your path to StepMania's song cache folder.
$cacheDir = '/var/www/smdir/Cache/Songs';

//location of stepmania local profile folder
$profileDir = '/var/www/smdir/Save/LocalProfiles';

//location of StepMania songs folder
$songsDir = '/var/www/smdir/Songs';

//Target url for uploading banner images to the server. This directory MUST exist before uploading banners.
$target_url = 'http://apache/banners.php';

//list of song packs/groups to ignore while scraping
$packsIgnore = array('~WIP');

if($security_key == ""){
    die("Security key must be set in .env file to use this software!");
}

if ( !is_dir( "/var/www/smdir/Cache/Songs" ) ) {
    //Cache directory is missing
    if ( !is_dir( "/var/www/smdir/Data" ) ) {
        //Data directory is also missing.
        die("Stepmania directory specified in .env file is not correct.");
    }else{
        //Data directory is found
        die("Songs cache directory not found in Stepmania directory. You must start Stepmania before running this software. Also, if you are not running Stepmania in portable mode, your Stepmania directory may be in \"AppData\".");
    }
}

?>