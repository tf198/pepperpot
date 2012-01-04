<?php

class State_File extends State_Base {
  function installed($local, $remote, $elevate=false, $owner=null, $group=null, $mode=0644) {
    $local_md5 = md5_file($local);
    $remote_md5 = $this->minion->task('file')->md5sum($remote);
    var_dump($local_md5, $remote_md5);
    if($local_md5 == $remote_md5) {
      if($this->minion->task('file')->mode($remote) != $mode) $this->minion->task('file')->chown($remote, $mode);
    } else {
      $this->minion->task('cmd')->copy_to($local, $remote, $mode, $elevate);
      if($owner || $group) $this->minion->task('file')->chown($owner, $group);
      
    }
    
  }
}