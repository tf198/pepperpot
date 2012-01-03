<?php

$phpseclib = realpath(dirname(__FILE__) . "/../../") . "/vendor/phpseclib";
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclib);
require "Net/SSH2.php";

class Task_SSH extends Task_Cmd {
  
  private $ssh;
  
  function __construct($minion) {
    parent::__construct($minion);
    $this->ssh = new Net_SSH2($minion->get('core', 'ip'));
    if(!$this->ssh->login($minion->get('core', 'username'), $minion->get('core', 'auth'))) {
    	throw new Task_Exception("Connection to {$minion->get('core', 'ip')} failed");
    }
  }
  
  function exec($cmd, &$output, &$ret) {
  	$ret_cmd = $cmd . '; echo __$?__';
  	$output = explode("\n", trim($this->ssh->exec($ret_cmd)));
  	$result = array_pop($output);
  	if(sscanf($result, "__%d__", $ret)!=1) {
  		var_dump($result); 
  		throw new Task_Exception("Failed to get return value");
  	}
  }
}
