<?php
class Task_System extends Task_Base {
  function os() {
    // windows
    $env = $this->minion->task('cmd')->system('echo %OS%', $ret);
    if($env != "%OS%") return "windows";
    
    // ubuntu
    if($this->minion->task('cmd')->run('test -f /etc/issue.net')==0) {
      $version = $this->minion->task('cmd')->system('cat /etc/issue.net', $ret);
      if(substr($version, 0, 6) == 'Ubuntu') return 'ubuntu';
    }
    
    return "unknown";
  }
  
  function kernel() {
  	switch($this->minion->os) {
  		case "ubuntu":
  			return $this->minion->task('cmd')->system('uname -r', $ret);
  		default:
  			throw new Task_Exception("Unabled to determine kernel for {$this->minion->os}");
  	}
  }
}
?>
