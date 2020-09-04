<?php

//Your database host. 'localhost' if you are running the DB on the same machine as the web server.
define('dbhost', 'localhost');
//Your database username.
define('dbuser', 'sw_user');
//Your database password.
define('dbpass', 'Y71o8QcQNn');
//Your database name.
define('db', 'SMsonglist');

//Your path to StepMania's song cache folder.
$cacheDir = 'C:/Games/StepMania 5.1/Cache/Songs';

//location of stepmania local profile folder
$profileDir = 'C:/Games/StepMania 5.1/Save/LocalProfiles';

//location of StepMania songs folder
$songsDir = 'C:/Games/StepMania 5.1/Songs';

//Target url for uploading banner images to the server. This directory MUST exist before uploading banners.
$target_url = 'http://localhost/banners.php';

//Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission.
$security_key = 'benis';

?>