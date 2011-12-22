<?php

class Task_Apt extends Task_Base {
  private $_packages = null;
  
  function __construct($instance) {
    parent::__construct($instance);
  }
  
  function update() {
    $this->grunt->task('cmd')->run('sudo apt-get update');
  }
  
  function install($name) {
    $result = $this->grunt->task('cmd')->run('sudo apt-get -y install ' . $name); 
    $this->_packages = null;
  }
  
  function available($name) {
    $output = $this->grunt->task('cmd')->run_stdout('apt-cache show ' . $name);
    return "4.0";
  }
  
  function current($name) {
    // use cached data unless $refresh
    $this->packages();
    return $this->_packages[$name];
  }
  
  function packages() {
    if($this->_packages == null) {
      $dummy = array('coreutils' => '1.1', 'openssh-server' => '2.1', 'apt' => '3.0');
      $data = $this->grunt->task('cmd')->run_stdout('dpkg -l', $dummy);
      // TODO: parse the data
      $this->_packages = $data;
    }
    return $this->_packages;
  }
}