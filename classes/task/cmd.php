<?php

class Task_Cmd extends Task_Base {
	/**
	* Execute a command and return the return code
	* @param string $cmd			command to execute
	* @param boolean $elevate	whether to run as root (default: false)
	* @param int $expected		expected return code (default: 0)
	*/
  function run($cmd, $elevate=false, $expected=0) {
    if($elevate) $cmd = $this->_elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if($ret != $expected) throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
  }
  
  /**
  * Execute a command and return the output
  * @param string $cmd			command to execute
  * @param boolean $elevate	whether to run as root (default: false)
  * @param int $expected		expected return code (default: 0)
  * @return array						stdout lines
  */
  function run_stdout($cmd, $elevate=false, $expected=0) {
  	if($elevate) $cmd = $this->_elevate($cmd);
  	$this->exec($cmd, $output, $ret);
    if($ret !== $expected) throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
  	return $output;
  }
  
  function _elevate($cmd) {
  	if($this->minion->os == 'windows') {
  		return $cmd;
  	} else {
  		return "sudo -n " . $cmd;
  	}
  }
  
  function system($cmd, &$ret) {
    $this->exec($cmd, $output, $ret);
    return $output[count($output)-1];
  }
  
  function exec($cmd, &$output, &$ret) {
  	$cmd .= " 2>&1";
  	exec($cmd, $output, $ret);
  }
  
  static function handler($instance) {
  	if(!$instance->get('core', 'local')) return new Task_SSH($instance);
    return new Task_Cmd($instance);
  }
}
