<?

class Task_SSH extends Task_Cmd {
  private $ssh;

  function __construct($minion) {
    parent::__construct($minion);
    $addr = $minion->get('core.ip');
    
    $this->ssh = ssh2_connect($addr, $minion->get('core.port', 22));
    if(!$this->ssh)
      throw new Task_Exception("Failed to connect to {$addr}");
    
    if(!ssh2_auth_password($this->ssh, $minion->get('core.username'), $minion->get('core.password')))
      throw new Task_Exception("Incorrect username or password for {$addr}");
  }
  
  function exec($cmd, &$output, &$ret) {
    $ret_cmd = $cmd . '; echo __$?__';
    
    $stream = ssh2_exec($this->ssh, $ret_cmd);
    stream_set_blocking($stream, true);
    
    $output = explode("\n", trim(stream_get_contents($stream)));
    $result = array_pop($output);
    if (sscanf($result, "__%d__", $ret) != 1) {
      var_dump($result);
      throw new Task_Exception("Failed to get return value");
    }
    $this->minion->log("SSH> {$cmd} [{$ret}]");
  }
  
  function copy_to($local, $remote, $create_mode=0644, $elevate=false) {
    if($elevate) {
      throw new Task_Exception("Not yet implemented");
    } else {
      $dest = $remote;
    }
    if(!ssh2_scp_send($this->ssh, $local, $dest, $create_mode))
      throw new Task_Exception("Failed to send file: '{$local}' > '{$remote}'");
    return true;
  }
  
  function copy_from($remote, $local, $elevate=false) {
    if(!ssh2_scp_recv($this->ssh, $remote, $local)) {
      throw new Task_Exception("Failed to receive file: '{$remote}' > '{$local}'");
    }
    return true;
  }
}