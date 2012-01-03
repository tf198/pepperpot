<?php

class Task_Base {
  protected $minion;
  protected $packages = array();
  
  function __construct($minion) {
    $this->minion = $minion;
    // check the required packages are installed
    foreach($this->packages as $package) $this->minion->state('package')->installed($package);
  }
  
  /**
  * In the future we can drop the $klass param and use static instead
  */
  static function handler($instance, $klass=null) {
  	return new $klass($instance);
  }

}
