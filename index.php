<?php

//var_dump($_SERVER);
//var_dump($_REQUEST);


include_once(dirname(__FILE__) . '/demo/config.php');
include_once(dirname(__FILE__) . '/AutoLoad.php');
AutoLoad::init();

(new WebServer)->run();
