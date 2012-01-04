<?php

$phpseclib = realpath(dirname(__FILE__) . "/../../") . "/vendor/phpseclib";
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclib);
require "Net/SSH2.php";

class Task_PHPSecLib extends Task_Cmd {
  const BOUNDARY = '==jv8dvngvn94d=';

  private $ssh;

  function __construct($minion) {
    parent::__construct($minion);
    $this->ssh = new Net_SSH2($minion->get('core.ip'), $minion->get('core.port', 22));
    $auth = $minion->get('core.authkey', false);
    if ($auth) {
      // load the key
    } else {
      $auth = $minion->get('core.password');
    }
    if (!$this->ssh->login($minion->get('core.username'), $auth)) {
      throw new Task_Exception("Connection to {$minion->get('core', 'ip')} failed");
    }
  }

  function exec($cmd, &$output, &$ret) {
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
  function copy_to($source, $dest, $elevate=false) {
    if ($elevate) {
      throw new Task_Exception("Not yet implemented");
    } else {
      $cmd = "cat > {$dest}";
    }
    $data = file_get_contents($source);

    // set a boundary marker
    $cmd .= '; echo ' . self::BOUNDARY;

    $this->ssh->write($cmd . "\n");
    $this->ssh->write($data . "\n" . chr(4));
    // need to read twice
    $this->ssh->read(self::BOUNDARY);
    echo $this->ssh->read(self::BOUNDARY);
  }

  function copy_from($source, $dest, $elevate=false) {
    $cmd = "cat \"$source\"";
    if ($elevate)
      $cmd = $this->_elevate($cmd);
    $this->exec($cmd, $output, $ret);
    if ($ret != 0)
      throw new Task_Exception("Failed to retrieve file: " . $source);
    file_put_contents($dest, implode(PHP_EOL, $output));
  }

}
