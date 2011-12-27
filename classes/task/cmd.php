<?php

class Task_Cmd extends Task_Base {
  function run($cmd, $elevate=false) {
    exec($this->_cmd($cmd, $elevate, 0), $output, $ret);
    return $ret;
  }
  
  function run_stdout($cmd, $elevate=false, $expected=0) {
    $last = exec($this->_cmd($cmd, $elevate, $expected), $output, $ret);
    if($ret != $expected) throw new Task_Exception($cmd . ": " .  $last);
    return $output;
  }

  function _cmd($cmd, $elevate, $expected) {
    if($elevate) $cmd = "sudo -n " . $cmd;
    $cmd .= " 2>&1";
    return $cmd;
  }
  
  function handler() {
  	if(!$this->grunt->is_local) return new Task_SSH($this->grunt);
    return $this;
  }
}
