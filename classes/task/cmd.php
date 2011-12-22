<?php

class Task_Cmd extends Task_Base {
  function run($cmd) {
    echo "CMD {$cmd}\n";
    return 0;
  }
  
  function run_stdout($cmd, $output=null) {
    $this->run($cmd);
    return $output;
  }
  
  function handler() {
    if(!$this->grunt->is_local()) return new Task_SSH($this->grunt);
    return $this;
  }
}