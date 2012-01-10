<?php
class Task_System extends Task_Base {
  public $cache_time = array('os' => 0, 'kernel' => 60, 'latency' => 60);
  
  function os() {
    // windows
    $env = $this->minion->task('cmd')->_system('echo %OS%', $ret);
    if($env != "%OS%") return "windows";
    
    // ubuntu
    $this->minion->task('cmd')->_system('test -f /etc/issue.net', $ret);
    if($ret==0) {
      $version = $this->minion->task('cmd')->_system('cat /etc/issue.net', $ret);
      if(substr($version, 0, 6) == 'Ubuntu') return 'ubuntu';
    }
    
    return "unknown";
  }
  
  function kernel() {
    switch($this->minion->speck('system.os')) {
      case "ubuntu":
  	return $this->minion->task('cmd')->_system('uname -r', $ret);
      default:
        throw new Task_Exception("Unabled to determine kernel");
    }
  }
  
  function time_offset() {
  	$date = $this->minion->task('cmd')->_system('date -R', $ret);
  	$ts = strtotime($date);
  	return time()-$ts;
  }
  
  function latency() {
  	$ts = microtime(true);
  	$result = $this->minion->task('cmd')->_system("echo Latency test", $ret);
        if($result!='Latency test') throw new Task_Exception("Unexpected response: " . $result);
  	return microtime(true) - $ts;
  }
}
?>
