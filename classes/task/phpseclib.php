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
    $keyfile = $minion->get('core.keyfile', false);
    if ($keyfile) {
      require_once "Crypt/RSA.php";
      $this->auth = new Crypt_RSA();
      $this->auth->loadKey(file_get_contents($keyfile));
    } else {
      $this->auth = $minion->get('core.password');
    }
  }
  
  function _ssh() {
    require_once "Net/SSH2.php";
    $ssh = new Net_SSH2($this->addr, $this->port);
    $this->minion->log("SSH> connect {$this->user}@{$this->addr} (port {$this->port})");
    if (!$ssh->login($this->user, $this->auth)) {
      throw new Task_Exception("Authentication failed for {$this->addr}");
    }
    return $ssh;
  }
  
  function _sftp() {
    require_once "Net/SFTP.php";
    $sftp = new Net_SFTP($this->addr, $this->port);
    $this->minion->log("SFTP> connect {$this->user}@{$this->addr} (port {$this->port})");
    if(!$sftp->login($this->user, $this->auth)) {
      throw new Task_Exception("Incorrect username or password for {$this->addr}");
    }
    return $sftp;
  }
  
  function _exec($cmd, &$output, &$ret) {
    if(!$this->ssh) $this->ssh = $this->_ssh();
    
    $ret_cmd = $cmd . '; echo __$?__';
    $output = explode("\n", trim($this->ssh->exec($ret_cmd)));
    // stderr is after result code
    $result = array_pop($output);
    $stderr = array();
    $ret = -1;
    while(sscanf($result, "__%d__", $ret) != 1 && $output) {
      $stderr[] = $result;
      $result = array_pop($output);
    }
    $this->minion->log("SSH> {$cmd} [{$ret}]");
  }

  /**
   * Copy a file to the remote server
   * This is a bit of a fudge as phpseclib doesn't currently support SCP so we use cat instead
   * @param type $source
   * @param type $dest
   * @param type $elevate
   */
  function copy_to($local, $remote, $mode=0644, $elevate=false) {
    if(!$this->sftp) $this->sftp = $this->_sftp();
    if ($elevate) {
      $dest = "/tmp/" . str_replace('/', '_', $remote);
    } else {
      $dest = $remote;
    }
    
    $data = file_get_contents($local);
    if(!$this->sftp->put($dest, $data))
            throw new Task_Exception("Failed to send file: '{$local}' > '{$dest}'");
    $this->minion->log("SFTP> 'local:{$local}' > 'remote:{$dest}'");
    if($elevate) {
    	$this->run("mv \"{$dest}\" \"{$remote}\"", true);
    }
    return true;
  }

  function copy_from($remote, $local, $elevate=false) {
    if(!$this->sftp) $this->sftp = $this->_sftp();
    if ($elevate) {
      throw new Task_Exception("Not yet implemented");
    } else {
      $source = $remote;
    }
    
    if(!$this->sftp->get($source, $local))
            throw new Task_Exception("Failed to receive file: '{$remote}' > '{$local}'");
    return true;
  }

}
