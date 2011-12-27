<?php

$phpseclib = realpath(dirname(__FILE__) . "/../../") . "/vendor/phpseclib";
var_dump($phpseclib);
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclib);
require "Net/SSH2.php";

class Task_SSH extends Task_Base {
  
  private $ssh;
  
  function __construct($grunt) {
    parent::__construct($grunt);
    $this->ssh = new Net_SSH2($grunt->ip_address);
    if(!$this->ssh->login($grunt->username, $grunt->auth)) {
    	throw new Task_Exception("Connection to {$grunt->ip_address} failed");
    }
  }
  
  function run($cmd) {
    $this->run_stdout($cmd, $elevate=false);
    return 0;
  }
  
  function run_stdout($cmd, $elevate=false, $expected=0) {
  	$exec = $cmd;
  	if($elevate) $exec = "sudo -n " . $exec;
  	$exec .= "; echo __\$?__";
  	$data = explode("\n", trim($this->ssh->exec($exec)));
  	$code = array_pop($data);
  	if(substr($code, -5) != "__{$expected}__") {
  		throw new Task_Exception($cmd . ": " . implode(", ", $data));
  	}
  	return $data; 
  }
}
