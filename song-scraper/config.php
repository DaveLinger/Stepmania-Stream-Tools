<?php

//Your database host. "localhost" if you are running the DB on the same machine as the web server.
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

//list of song packs/groups to ignore while scraping
$packsIgnore = array('~WIP');

//Target url for uploading banner images to the server. This directory MUST exist before uploading banners.
$target_url = 'https://domain.com/sm5/banners.php';

//Target url for POSTING updates to the server. This directory MUST exist before running any scraper.
$target_url_status = 'https://domain.com/sm5/status.php';

//Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission.
$security_key = 'any-secret-here';

?>