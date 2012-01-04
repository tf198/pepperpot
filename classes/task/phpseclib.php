<?php

$phpseclib = realpath(dirname(__FILE__) . "/../../") . "/vendor/phpseclib";
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclib);

class Task_PHPSecLib extends Task_Cmd {
  const BOUNDARY = '==jv8dvngvn94d=';

  private $ssh, $sftp;

  private $addr, $port, $user, $auth;
  
  function __construct($minion) {
    parent::__construct($minion);
    
    $this->addr = $minion->get('core.ip');
    $this->port = $minion->get('core.port', 22);
    $this->user = $minion->get('core.username');
    $this->auth = $minion->get('core.authkey', false);
    if ($this->auth) {
      throw new Task_Exception("Not yet implemented");
    } else {
      $this->auth = $minion->get('core.password');
    }
  }
  
  function _ssh() {
    require "Net/SSH2.php";
    $ssh = new Net_SSH2($this->addr, $this->port);
    if (!$ssh->login($this->user, $this->auth)) {
      throw new Task_Exception("Incorrect username or password for {$this->addr}");
    }
    return $ssh;
  }
  
  function _sftp() {
    require "Net/SFTP.php";
    $sftp = new Net_SFTP($this->addr, $this->port);
    if(!$sftp->login($this->user, $this->auth)) {
      throw new Task_Exception("Incorrect username or password for {$this->addr}");
    }
    return $sftp;
  }
  
  function exec($cmd, &$output, &$ret) {
    if(!$this->ssh) $this->ssh = $this->_ssh();
    $ret_cmd = $cmd . '; echo __$?__';
    $output = explode("\n", trim($this->ssh->exec($ret_cmd)));
    $result = array_pop($output);
    if (sscanf($result, "__%d__", $ret) != 1) {
      var_dump($result);
      throw new Task_Exception("Failed to get return value");
    }
  }

  /**
   * Copy a file to the remote server
   * This is a bit of a fudge as phpseclib doesn't currently support SCP so we use cat instead
   * @param type $source
   * @param type $dest
   * @param type $elevate
   */
  function copy_to($local, $remote, $elevate=false) {
    if(!$this->sftp) $this->sftp = $this->_sftp();
    if ($elevate) {
      throw new Task_Exception("Not yet implemented");
    } else {
      $dest = $remote;
    }
    
    $data = file_get_contents($local);
    $this->sftp->put($remote, $data);
  }

  function copy_from($source, $dest, $elevate=false) {
    if(!$this->sftp) $this->sftp = $this->_sftp();
    $cmd = "cat \"$source\"";
    if ($elevate)
      $cmd = $this->_elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if ($ret != 0)
      throw new Task_Exception("Failed to retrieve file: " . $source);
    file_put_contents($dest, implode(PHP_EOL, $output));
  }

}
