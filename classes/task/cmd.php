<?php

class Task_Cmd extends Task_Base {
  function run($cmd) {
    if($elevate) $cmd = "sudo -n " . $cmd;
    $this->exec($cmd, $output, $ret);
    return $ret;
  }
  
  function run_stdout($cmd, $elevate=false, $expected=0) {
  	if($elevate) $cmd = "sudo -n " . $cmd;
  	$this->exec($cmd, $output, $ret);
  	return $output;
  }
  
  function exec($cmd, &$output, &$ret) {
  	$cmd .= " 2>&1";
  	exec($cmd, $output, $ret);
  }
  
  static function handler($instance) {
  	if(!$instance->is_local) return new Task_SSH($instance);
    return parent::handler($instance);
  }
}
