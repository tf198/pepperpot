<?php

class Task_Base {
  protected $grunt;
  protected $packages = array();
  
  function __construct($grunt) {
    $this->grunt = $grunt;
    // check the required packages are installed
    foreach($this->packages as $package) $this->grunt->state('package')->installed($package);
  }
  
  /**
  * In the future we can drop the $klass param and use static instead
  */
  static function handler($instance, $klass=null) {
  	return new $klass($instance);
  }

}
