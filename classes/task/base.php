<?php

class Task_Base {
  protected $grunt;
  protected $packages = array();
  protected $_cache = array();
  
  function __construct($grunt) {
    $this->grunt = $grunt;
    // check the required packages are installed
    foreach($this->packages as $package) $this->grunt->state('package')->installed($package);
  }
  
  /**
  * Cached titbits of information
  */
  function grain($name) {
    if(!isset($this->_cache[$name])) {
      $this->_cache[$name] = $this->$name();
    }
    return $this->_cache[$name];
  }
  
  /**
  * In the future we can drop the $klass param and use static instead
  */
  static function handler($instance, $klass=null) {
  	return new $klass($instance);
  }

}
