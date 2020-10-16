<?php

include_once(dirname(__FILE__) . '/demo/config.php');
include_once(dirname(__FILE__) . '/AutoLoad.php');
AutoLoad::init();

(new WebServer)->run();