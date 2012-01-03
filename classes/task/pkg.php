<?php

abstract class Task_Pkg extends Task_Base {
  static function handler($minion) {
  	switch($minion->speck('system.os')) {
      case 'ubuntu':
      case 'debian':
        return new Task_Apt($minion);
      default:
        throw new Task_Exception("No Pkg implementation available");
    }
  }
  
  /**
  * Reload all packages from source servers
  */
  abstract function reload();
  
  /**
  * Get list of all installed packages
  * @return array 					array('package_name' => 'version', ...)
  */
 	abstract function packages();
 	
 	/**
 	* Install the named package
 	* @param string $name			package name
 	*/
 	abstract function install($name);
 	
 	abstract function current($name);
 	
 	abstract function available($name);
 	
 	function up_to_date($name) {
 		return ($this->current($name) == $this->available($name));
 	}
}
