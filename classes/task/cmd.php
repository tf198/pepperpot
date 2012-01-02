<?php

class Task_Cmd extends Task_Base {
  function run($cmd, $elevate=false) {
    if($elevate) $cmd = "sudo -n " . $cmd;
    $this->exec($cmd, $output, $ret);
    return $ret;
  }
  
  function run_stdout($cmd, $elevate=false, $expected=0) {
  	if($elevate) $cmd = "sudo -n " . $cmd;
  	$this->exec($cmd, $output, $ret);
    if($ret!==0) throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
  	return $output;
  }
  
  function system($cmd, &$ret) {
    $this->exec($cmd, $output, $ret);
    return $output[count($output)-1];
  }
  
  function exec($cmd, &$output, &$ret) {
  	$ret_cmd = $cmd . " 2>&1";
  	exec($ret_cmd, $output, $ret);
  }
  
  static function handler($instance) {
  	if(!$instance->param('local', false)) return new Task_SSH($instance);
    return new Task_Cmd($instance);
  }
}
