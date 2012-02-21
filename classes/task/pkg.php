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

}
