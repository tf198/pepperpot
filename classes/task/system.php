<?php
class Task_System extends Task_Base {
  function os() {
    // windows
    $env = $this->grunt->task('cmd')->system('echo %OS%', $ret);
    if($env != "%OS%") return "windows";
    
    // ubuntu
    if($this->grunt->task('cmd')->run('test -f /etc/issue.net')==0) {
      $version = $this->grunt->task('cmd')->system('cat /etc/issue.net', $ret);
      if(substr($version, 0, 6) == 'Ubuntu') return 'ubuntu';
    }
    
    return "unknown";
  }
  
  function kernel() {
  	switch($this->grunt->os) {
  		case "ubuntu":
  			return $this->grunt->task('cmd')->system('uname -r', $ret);
  		default:
  			throw new Task_Exception("Unabled to determine kernel for {$this->grunt->os}");
  	}
  }
}
?>
