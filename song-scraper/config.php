<?php

//Your database host. 'localhost' if you are running the DB on the same machine as the web server.
define('dbhost', '');
//Your database username.
define('dbuser', '');
//Your database password.
define('dbpass', '');
//Your database name.
define('db', '');

//Your path to StepMania's song cache folder.
$cacheDir = 'C:/Users/Admin/AppData/Roaming/StepMania 5.1/Cache/Songs';

//location of stepmania local profile folder
$profileDir = 'C:/Users/Admin/AppData/Roaming/StepMania 5.1/Save/LocalProfiles';

//location of StepMania songs folder
$songsDir = 'D:/StepMania 5.1/Songs';

//Target url for uploading banner images to the server. This directory MUST exist before uploading banners.
$target_url = 'https://domain.tld/sm5/banners.php';

//Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission.
$security_key = 'any-secret-here';

?>