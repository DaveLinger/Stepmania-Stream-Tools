<?php

//Check for docker variables to use first

if(getenv("MYSQL_DATABASE") != ""){
    $dbname = getenv("MYSQL_DATABASE");
    define('dbhost', 'mysql');
    define('db', $dbname);
}else{
    define('dbhost', '');
    define('db', '');
}

if(getenv("MYSQL_USER") != ""){
    $dbuser = getenv("MYSQL_USER");
    define('dbuser', $dbuser);
}else{
    define('dbuser', '');
}

if(getenv("MYSQL_PASSWORD") != ""){
    $dbpass = getenv("MYSQL_PASSWORD");
    define('dbpass', $dbpass);
}else{
    define('dbpass', '');
}

if(getenv("SECRET_KEY") != ""){
    $security_key = getenv("SECRET_KEY");
}else{
    $security_key = "";
}

//List of banned songs, comma separated. This is going to be replaced by database flags soon. Example: Array(69,420,80085);
$banned = Array();
$GLOBALS["banned"] = $banned;

?>