<?php
$path = dirname(__FILE__);

// setup autoloader
require_once($path . "/../classes/pepperpot.php");
PepperPot::register();

require_once($path . "/MockCmd/TestCase.php");