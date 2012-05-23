<?php

class Task_Cmd extends Task_Base {

  /**
   * Execute a command and return the return the last line of output
   * @param string 		$cmd			command to execute
   * @param	string		$user			user to run as or bool (default: false)
   * @param int 		$expected		expected return code (default: 0)
   */
  function run($cmd, $user=null, $expected=0) {
    if ($user) $cmd = $this->run_as($cmd, $user);
    
    $this->exec($cmd, $output, $ret);
    if ($ret != $expected)
      throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
    return ($output) ? $output[count($output) - 1] : '';
  }

  /**
   * Execute a command and return the output
   * throws an exception if return code isn't what is expected
   * @param string		$cmd			command to execute
   * @param	string		$user			user to run as or bool (default: false)
   * @param int 		$expected		expected return code (default: 0)
   * @return array						stdout lines
   */
  function run_stdout($cmd, $user=null, $expected=0) {
    if ($user) $cmd = $this->run_as($cmd, $user);
    
    $this->exec($cmd, $output, $ret);
    if ($ret !== $expected)
      throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
    return $output;
  }
  
  /**
   * Execute a command and check the return code
   * @param string 		$cmd		command to execute
   * @param	string		$user		user to run as or bool (default: false)
   * @param int			$expected	expected return code (default: 0)
   * @return boolean				whether the return code was as expected
   */
  function run_ret($cmd, $user=false, $expected=0) {
  	if ($user) $cmd = $this->run_as($cmd, $user);
  	
  	$this->system($cmd, $ret);
  	return ($ret == $expected);
  }

	/**
	* Modify command to run as a different user
	* @param	string	$cmd	command to execute
	* @param	string	$user	user to run as
	* @return string			elevated command
	*/
  function run_as($cmd, $user=null) {
    if ($this->minion->speck('system.os') == 'windows') {
    	return $cmd;
    } else {
    	$sudo = "sudo -n ";
    	if(is_string($user) && $user!='root') $sudo .= "-u " . escapeshellcmd($user) . " ";
      	return $sudo . $cmd;
    }
  }

	/**
	* Emulate system() call using underlying transport
	*/
  function system($cmd, &$ret) {
    $this->exec($cmd, $output, $ret);
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

  function stat($file, $elevate=false) {
  	if($elevate) throw new Task_NotImplemented("No elevated stat implemtented yet");
  	return stat($file);
  }
  
  function copy_to($local, $remote, $create_mode, $elevate=false) {
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
  
    if (!$instance->get('config.local', false)) {
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
