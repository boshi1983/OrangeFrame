<?php
defined('DEBUG') ? null : define('DEBUG', true);
//---root path
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('SITE_ROOT') ? null : define('SITE_ROOT', dirname(__FILE__));
defined('ROOT_PATH') ? null : define('ROOT_PATH', SITE_ROOT . DS);
defined('RES_PATH') ? null : define('RES_PATH', '/var/www/OrangeFrame/');
defined('DOMAIN') ? null : define('DOMAIN', 'pdp.test');
defined('CONTROLLER_PATH') ? null : define('CONTROLLER_PATH', ROOT_PATH . 'Controller');

//---redis-master
defined('REDIS_MASTER_HOST') ? null : define('REDIS_MASTER_HOST', 'redis');
defined('REDIS_MASTER_PORT') ? null : define('REDIS_MASTER_PORT', '6379');
defined('REDIS_MASTER_PW') ? null : define('REDIS_MASTER_PW', '123');

//---redis-slave
//defined('REDIS_SLAVE_HOST') ? null : define('REDIS_SLAVE_HOST', 'redis');
//defined('REDIS_SLAVE_PORT') ? null : define('REDIS_SLAVE_PORT', '6379');
//defined('REDIS_SLAVE_PW') ? null : define('REDIS_SLAVE_PW', '123456');

//---mysql-master
defined('MYSQL_MASTER_HOST') ? null : define('MYSQL_MASTER_HOST', 'mysql');
defined('MYSQL_MASTER_PORT') ? null : define('MYSQL_MASTER_PORT', '3306');
defined('MYSQL_MASTER_USERNAME') ? null : define('MYSQL_MASTER_USERNAME', 'phpstorm');
defined('MYSQL_MASTER_PASSWORD') ? null : define('MYSQL_MASTER_PASSWORD', '123456');
defined('MYSQL_MASTER_DBNAME') ? null : define('MYSQL_MASTER_DBNAME', 'demo');
defined('MYSQL_MASTER_CHARSET') ? null : define('MYSQL_MASTER_CHARSET', 'utf8mb4');

//---mysql-slave
//defined('MYSQL_SLAVE_HOST') ? null : define('MYSQL_SLAVE_HOST', 'mysql');
//defined('MYSQL_SLAVE_PORT') ? null : define('MYSQL_SLAVE_PORT', '3306');
//defined('MYSQL_SLAVE_USERNAME') ? null : define('MYSQL_SLAVE_USERNAME', 'root');
//defined('MYSQL_SLAVE_PASSWORD') ? null : define('MYSQL_SLAVE_PASSWORD', '123456');
//defined('MYSQL_SLAVE_DBNAME') ? null : define('MYSQL_SLAVE_DBNAME', 'demo');
//defined('MYSQL_SLAVE_CHARSET') ? null : define('MYSQL_SLAVE_CHARSET', 'utf8mb4');