<?php

class Task_SSH extends Task_Cmd {
  
  function __construct($grunt) {
    parent::__construct($grunt);
  }
  
  function run($cmd) {
    echo "SSH {$cmd}\n";
    return 0;
  }
}