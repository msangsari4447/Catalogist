<?php

putenv('WORDPRESS_DB_HOST=db:3306');
putenv('WORDPRESS_DB_NAME=catalogist');
putenv('WORDPRESS_DB_USER=catalogist');
putenv('WORDPRESS_DB_PASSWORD=catalogist_db');

require_once '/var/www/html/wp-load.php';
require_once __DIR__ . '/vendor/autoload.php';