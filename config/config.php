<?php
date_default_timezone_set("UTC");

define ('ROOT', $_SERVER['DOCUMENT_ROOT']);

define ('LANG', 'en');

define ('USE_MEMCACHED', false);
define ('SELECT_LIMIT', 10000);
define ('RETURN_ARGS', false);

define ('DB_HOST', getenv('DB_HOST'));
define ('DB_PORT', getenv('DB_PORT'));
define ('DB_NAME', getenv('DB_NAME'));
define ('DB_USER', getenv('DB_USER'));
define ('DB_PASS', getenv('DB_PASS'));

define ('NEXTBIGSOUND_API', 'https://api.nextbigsound.com/events/v1/entity/');
define ('ACCESS_TOKEN', '8c089170d31ea3b11f1ea65dbfc8ea46');
