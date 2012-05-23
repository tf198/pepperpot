<?php

abstract class Task_Pkg extends Task_Base {

  static function handler($minion, $klass=null) {
    switch($minion->speck('system.os')) {
      case 'ubuntu':
      case 'debian':
        return new Task_Apt($minion);
      default:
        throw new Task_Exception("No Pkg implementation available");
    }
  }

  /** TASKS **/
  
  /**
   * Reload all packages from source servers
   */
  abstract function reload();

  /**
   * Get list of all installed packages
   * @return array        array('package_name' => 'version', ...)
   */
  abstract function packages();

  /**
   * Install the named package
   * @param string $name   package name
   */
  abstract function install($name);

  /**
   * Get the current version for the named package
   * @param string $name    package name
   * @return string         version string or null if not installed
   */
  abstract function current($name);

  /**
   * Get the highest available version for the named pacakge
   * @param string $name    package name
   * @return string         version string
   */
  abstract function available($name);

  /**
   * Check whether the current package is the most recent available
   * @param string $name    package name
   * @return boolean
   */
  function up_to_date($name) {
    return ($this->current($name) == $this->available($name));
  }
  
  /** STATES **/
  
  /**
   * Ensure the package is installed;
   * @param string $name
   */
  function ensure_installed($name) {
  	$this->ensure_version($name, "");
  }
  
  /**
   * Ensure the package is installed and is the latest version available
   * @param	string	$name
   */
  function ensure_latest($name) {
  	$latest = $this->available($name);
  	$this->ensure_version($name, $latest);
  }
  
  /**
   * Ensure the package is installed and is at least version $min
   * @param string $name
   * @param string $min		minimum version - will do string based comparison
   * @return boolean
   */
  function ensure_version($name, $min) {
  	$current = $this->current($name);
  
  	if($current!=null && $current >= $min) return;
  	
  	if($this->available($name) < $min) throw new Task_Exception("No version of '{$name}' available greater than '{$min}'");
  	$this->install($name);
  	$new = $this->current($name);
  	if($new < $min) throw new Task_Exception("Unable to install a version of '{$name}' that greater than '{$min}'");
  }

}
