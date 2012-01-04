<?php

class Task_Cmd extends Task_Base {

  /**
   * Execute a command and return the return code
   * @param string $cmd			command to execute
   * @param boolean $elevate	whether to run as root (default: false)
   * @param int $expected		expected return code (default: 0)
   */
  function run($cmd, $elevate=false, $expected=0) {
    if ($elevate)
      $cmd = $this->_elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if ($ret != $expected)
      throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
  }

  /**
   * Execute a command and return the output
   * @param string $cmd			command to execute
   * @param boolean $elevate	whether to run as root (default: false)
   * @param int $expected		expected return code (default: 0)
   * @return array						stdout lines
   */
  function run_stdout($cmd, $elevate=false, $expected=0) {
    if ($elevate)
      $cmd = $this->_elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if ($ret !== $expected)
      throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
    return $output;
  }

  function _elevate($cmd) {
    if ($this->minion->speck('system.os') == 'windows') {
      return $cmd;
    } else {
      return "sudo -n " . $cmd;
    }
  }

  function system($cmd, &$ret) {
    $this->exec($cmd, $output, $ret);
    return ($output) ? $output[count($output) - 1] : '';
  }

  function exec($cmd, &$output, &$ret) {
    $cmd .= " 2>&1";
    exec($cmd, $output, $ret);
  }
  
  function copy_to($local, $remote, $elevate=false) {
  	$cmd = "cp \"{$local}\" \"{$remote}\"";
  	if($elevate) $cmd = $this->_elevate($cmd);
  	$this->exec($cmd, $output, $ret);
  	if($ret!=0) throw new Task_Exception("Failed to copy file {$local}");	
  	return true;
 	}
  
  function copy_from($remote, $local, $elevate=false) {
  	return $this->copy_to($remote, $local, $elevate);		
  }

  static function handler($instance) {
  
    if (!$instance->get('core.local', false)) {
      $klass = function_exists('ssh2_connect') ? "Task_SSH" : "Task_PHPSecLib";
      return new $klass($instance);
    }
    return new Task_Cmd($instance);
  }

}
