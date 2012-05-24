<?php

/**
 * Basic autoloader for PepperPot classes
 * Note: PepperPot DOES NOT yet follow PSR-0, but will soon
 * @author Tris Forster
 *
 */
class PepperPot {
	
  /**
   * @var string
   */
  static $base = null;
  
  /**
   * Autoloader for PepperPot classes
   * @param string $class
   */
  static function autoload($class) {
    if(!self::$base) self::$base = dirname(__FILE__) . '/';
    $file = self::$base . str_replace('_', '/', strtolower($class)) . '.php';
    if(file_exists($file)) include($file);
  }
  
  /**
   * Register the PepperPot autoloader
   */
  static function register() {
    spl_autoload_register('PepperPot::autoload');
  }
  
  /**
   * @deprecated
   * @param string $cmd
   */
  static function cmd($cmd) {
    $args = func_get_args();
    $cmd = array_shift($args);
    return $cmd . " " . implode(' ', array_map('escapeshellarg', $args));
  }
}
