<?php
class Task_System extends Task_Base {
  public $cache_time = array('os' => 0, 'kernel' => 0, 'latency' => 60);
  
  function os() {
    if($this->minion->speck('system.kernel')=='windows_nt') return 'windows';
    
    // ubuntu - a bit tricky actually
    $this->minion->task('cmd')->_system('test -f /etc/issue.net', $ret);
    if($ret==0) {
      $version = $this->minion->task('cmd')->_system('cat /etc/issue.net', $ret);
      if(substr($version, 0, 6) == 'Ubuntu') return 'ubuntu';
    }
    
    return "unknown";
  }
  
  function kernel() {
    $env = $this->minion->task('cmd')->run('echo %OS%');
    if($env != "%OS%") return strtolower($env);
    
    return strtolower($this->minion->task('cmd')->run('uname -s'));
  }
  
  function kernel_version() {
    switch($this->minion->speck('system.kernel')) {
      case 'linux':
        return $this->minion->task('cmd')->run('uname -r');
      case 'windows_nt':
        $data = $this->minion->task('cmd')->run('cmd /c ver');
        if(!preg_match('/^Microsoft Windows (.*)\[Version ([0-9\.]+)\]$/', $data, $matches)) throw new Task_Exception("Failed to parse version: {$data}");
        return $matches[2];
      default:
        throw new Task_NotImplemented();
    }
  }
  
  function cpuinfo() {
    switch($this->minion->speck('system.kernel')) {
      case 'linux':
        $data = $this->minion->task('cmd')->run_stdout('cat /proc/cpuinfo');
        return $this->_parse_keypairs($data, ':');
      case 'windows_nt':
        $data = $this->minion->task('cmd')->run_stdout('wmic cpu');
        $len = strlen($data[0]);
        $start = 0;
        $result = array();
        for($i=0; $i<$len-1; $i++) {
          if($data[0][$i]==' ' && $data[0][$i+1]!=' ') {
            $key = trim(substr($data[0], $start, $i-$start));
            $result[$key] = trim(substr($data[1], $start, $i-$start));
            $start = $i;
          }
        }
        return $result;
      default:
        throw new Task_NotImplemented;
    }
  }
  
  function _parse_keypairs($data, $sep='=') {
    $result = array();
    foreach($data as $line) {
      $parts = explode($sep, $line, 2);
      $key = trim($parts[0]);
      if($key) $result[$key] = trim($parts[1]);
    }
    return $result;
  }
  
  function hostname() {
    // same for everything I think
    return $this->minion->task('cmd')->run('hostname');
  }
  
  function time() {
    switch($this->minion->speck('system.kernel')) {
      case 'linux':
        $date = $this->minion->task('cmd')->run('date -R');
        return strtotime($date);
      default:
        throw new Task_NotImplemented();
    }
  }
  
  function time_offset() {
    return time() - $this->time();
  }
  
  function expire($key) {
    return $this->minion->expire($key);
  }
}
?>
