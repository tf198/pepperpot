<?php

/**
 * Abstract package manager Task
 * @author Tris Forster
 */
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

  /**
   * Reload all packages from source servers
   * @pepper action
   */
  abstract function reload();

  /**
   * Get list of all installed packages
   * @pepper speck
   * @return multitype:string        package_name => version
   */
  abstract function packages();

  /**
   * Install the named package
   * @pepper action
   * @param string $name   package name
   */
  abstract function install($name);

  /**
   * Get the current version for the named package
   * @pepper speck
   * @param string $name    package name
   * @return string         version string or null if not installed
   */
  abstract function current($name);

  /**
   * Get the highest available version for the named pacakge
   * @pepper speck
   * @param string $name    package name
   * @return string         version
   */
  abstract function available($name);

  /**
   * Check whether the current package is the most recent available
   * @pepper speck
   * @param string $name    package name
   * @return boolean
   */
  function up_to_date($name) {
    return ($this->current($name) == $this->available($name));
  }
  
  /**
   * Ensure the package is installed;
   * @pepper state
   * @param string $name
   */
  function ensure_installed($name) {
  	$this->ensure_version($name, "");
  }
  
  /**
   * Ensure the package is installed and is the latest version available
   * @pepper state
   * @param	string	$name
   */
  function ensure_latest($name) {
  	$latest = $this->available($name);
  	$this->ensure_version($name, $latest);
  }
  
  /**
   * Ensure the package is installed and is at least version $min
   * @pepper state
   * @param string $name
   * @param string $min		minimum version - will do string based comparison
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
