<?php

class Task_Base {
  protected $grunt;
  protected $packages = array();
  
  function __construct($grunt) {
    $this->grunt = $grunt;
    // check the required packages are installed
    foreach($this->packages as $package) $this->grunt->state('package')->installed($package);
  }
  
  static function handler($instance) {
  	return new static($instance);
  }

}
