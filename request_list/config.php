<?php

//Your database host. "localhost" if you are running the DB on the same machine as the web server.
define("dbhost", "");
//Your database username.
define("dbuser", "");
//Your database password.
define("dbpass", "");
//Your database name.
define("db", "");

//Upload directory for banner pack images (absolute directory on server)
$uploaddir = '/var/www/html/sm5/images/packs';

//Security key. Set this to anything. All incoming requests (like from moobot) will have to include this key or they'll be discarded.
//This way people can't hit your endpoints directly without permission.
$security_key = "any-secret-here";

//Broadcaster List. Define an array to associate broadcaster names with StepMania profile names.
//This is only required if your StepMania setup is used by more than 1 twitch account.
$broadcasters = array(
						//twitch id			//SM5 profile
						'ddrdave' 		=> 	'Dave',
						'mrtwinkles' => 	'MRT'
					 );

?>