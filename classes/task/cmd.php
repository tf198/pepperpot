<?php

class Task_Cmd extends Task_Base {

  /**
   * Execute a command and return the return the last line of output
   * @param string $cmd			command to execute
   * @param boolean $elevate	whether to run as root (default: false)
   * @param int $expected		expected return code (default: 0)
   */
  function run($cmd, $elevate=false, $expected=0) {
    if ($elevate)
      $cmd = $this->elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if ($ret != $expected)
      throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
    return ($output) ? $output[count($output) - 1] : '';
  }

  /**
   * Execute a command and return the output
   * throws an exception if return code isn't what is expected
   * @param string $cmd			command to execute
   * @param boolean $elevate	whether to run as root (default: false)
   * @param int $expected		expected return code (default: 0)
   * @return array						stdout lines
   */
  function run_stdout($cmd, $elevate=false, $expected=0) {
    if ($elevate)
      $cmd = $this->elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if ($ret !== $expected)
      throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
    return $output;
  }

	/**
	* Elevate a cmd
	*/
  function elevate($cmd) {
    if ($this->minion->speck('system.os') == 'windows') {
      return $cmd;
    } else {
      return "sudo -n " . $cmd;
    }
  }

	/**
	* Emulate system() call using underlying transport
	*/
  function system($cmd, &$ret) {
    $this->_exec($cmd, $output, $ret);
    return ($output) ? $output[count($output) - 1] : '';
  }

	/**
	* Emulate exec() call using underlying transport
	*/
  function exec($cmd, &$output, &$ret) {
    $cmd .= " 2>&1";
    exec($cmd, $output, $ret);
    $this->minion->log("CMD> {$cmd} [{$ret}]");
  }
  
  function copy_to($local, $remote, $elevate=false) {
  	$cmd = "cp \"{$local}\" \"{$remote}\"";
  	if($elevate) $cmd = $this->_elevate($cmd);
  	$this->_exec($cmd, $output, $ret);
  	if($ret!=0) throw new Task_Exception("Failed to copy file {$local}");	
  	return true;
 	}
  
  function copy_from($remote, $local, $elevate=false) {
  	return $this->copy_to($remote, $local, $elevate);		
  }
  
  function latency() {
  	$ts = microtime(true);
  	$result = $this->minion->task('cmd')->_system("echo Latency test", $ret);
  	if($result != 'Latency test') throw new Task_Exception("Unexpected output: {$result}");
  	return microtime(true) - $ts;
  }
  
  static function handler($instance, $klass=null) {
  
    if (!$instance->get('core.local', false)) {
      $klass = function_exists('ssh2_connect') ? "Task_SSH" : "Task_PHPSecLib";
      return new $klass($instance);
    }
    return new Task_Cmd($instance);
  }
  
  static function construct($cmd) {
  	$args = func_get_args();
  	for($i=0, $c=count($args); $i<$c; $i++) {
  		if(substr($args[$i], 0, 1) != '-') $args[$i] = escapeshellarg($args[$i]);
  	}
  	return implode(' ', $args);
  }

}
