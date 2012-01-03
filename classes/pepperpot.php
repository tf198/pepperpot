<?php

class PepperPot {
  static $base = null;
  
  static function autoload($class) {
    if(!self::$base) self::$base = dirname(__FILE__) . '/';
    $file = self::$base . str_replace('_', '/', strtolower($class)) . '.php';
    if(file_exists($file)) include($file);
  }
  
  static function register() {
    spl_autoload_register('PepperPot::autoload');
  }
}
