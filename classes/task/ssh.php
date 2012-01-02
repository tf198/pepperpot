<?php

$phpseclib = realpath(dirname(__FILE__) . "/../../") . "/vendor/phpseclib";
var_dump($phpseclib);
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclib);
require "Net/SSH2.php";

class Task_SSH extends Task_Cmd {
  
  private $ssh;
  
  function __construct($grunt) {
    parent::__construct($grunt);
    $this->ssh = new Net_SSH2($grunt->ip_address);
    if(!$this->ssh->login($grunt->username, $grunt->auth)) {
    	throw new Task_Exception("Connection to {$grunt->ip_address} failed");
    }
  }
  
  function exec($cmd, &$output, &$ret) {
  	$ret_cmd = $cmd . '; echo __$?__';
  	$output = explode("\n", trim($this->ssh->exec($ret_cmd)));
  	$result = array_pop($output);
  	var_dump($result);
  	if(sscanf($result, "__%d__", $ret)!=1) {
  		var_dump($result); 
  		throw new Task_Exception("Failed to get return value");
  	}
  	if($ret!==0) throw new Task_Exception("Cmd failed '{$cmd}': " . implode(', ', $output));
  }
}
