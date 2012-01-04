<?php

class Task_File extends Task_Base {
  
  function md5sum($file) {
    $this->minion->task('cmd')->exec("md5sum \"{$file}\"", $output, $ret);
    return ($ret==0) ? substr($output[0], 0, 32) : "";
  }
}