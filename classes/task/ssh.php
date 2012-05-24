<?
/**
 * SSH2lib command implementation
 * @author Tris Forster
 * @package PepperPot/Task
 */
class Task_SSH extends Task_Cmd {
  private $ssh;

  function __construct($minion) {
    parent::__construct($minion);
    $addr = $minion->get('config.host');
    $port = $minion->get('config.port', 22);
    $user = $minion->get('config.username');
    $minion->log("SSH> connect {$user}@{$addr} (port {$port})");
    
    $this->ssh = ssh2_connect($addr, $port);
    if(!$this->ssh)
      throw new Task_Exception("Failed to connect to {$addr}");
    
    $keyfile = $this->minion->get('config.keyfile', false);
    if($keyfile) {
      if(!ssh2_auth_pubkey_file($this->ssh, $user, $keyfile . ".pub", $keyfile))
        throw new Task_Exception("Authentication failed for {$user}@{$addr}");
    } else {
      if(!ssh2_auth_password($this->ssh, $user, $minion->get('config.password')))
        throw new Task_Exception("Incorrect username or password for {$addr}");
    }
  }
  
  function exec($cmd, &$output, &$ret) {
    $ret_cmd = $cmd . '; echo __$?__';
    
    $stream = ssh2_exec($this->ssh, $ret_cmd);
    stream_set_blocking($stream, true);
    
    $output = explode("\n", trim(stream_get_contents($stream)));
    $result = array_pop($output);
    if (sscanf($result, "__%d__", $ret) != 1) {
      throw new Task_Exception("Failed to get return value");
    }
    $this->minion->log("SSH> {$cmd} [{$ret}]");
  }
  
  function copy_to($local, $remote, $mode=0644, $user=false) {
    if($user) {
      throw new Task_Exception("Not yet implemented");
    } else {
      $dest = $remote;
    }
    if(!ssh2_scp_send($this->ssh, $local, $dest, $mode))
      throw new Task_Exception("Failed to send file: '{$local}' > '{$remote}'");
    return true;
  }
  
  function copy_from($remote, $local, $mode=0644, $user=false) {
    if(!ssh2_scp_recv($this->ssh, $remote, $local)) {
      throw new Task_Exception("Failed to receive file: '{$remote}' > '{$local}'");
    }
    return true;
  }
}
