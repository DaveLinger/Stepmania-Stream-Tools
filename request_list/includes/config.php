<?php

//Your database host. "localhost" if you are running the DB on the same machine as the web server.
define('dbhost', 'mysql');
//Your database username.
define('dbuser', 'sw_user');
//Your database password.
define('dbpass', 'Y71o8QcQNn');
//Your database name.
define('db', 'sm_software');

//List of banned songs, comma separated. This is going to be replaced by database flags soon. Example: Array(69,420,80085);
$banned = Array();
$GLOBALS["banned"] = $banned;

//Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission.
$security_key = "type-literally-anything-here";

?>