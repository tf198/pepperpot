<?php

/**
 * A system instance - ready to do your bidding
 */
class Grunt {
  
  private $_components = array('task' => array(), 'state' => array());
  
  public $ip_address, $is_local, $username, $auth;
  
  /**
  * @param string $ip_address 			system ip
  * @param string $username 				username
  * @param string|Crypt_RSA	$auth 	password or authentication key
  */
  function __construct($ip_address, $username, $auth) {
  	$this->ip_address = $ip_address;
  	$this->is_local = ($ip_address == '127.0.0.1');
  	$this->username = $username;
  	$this->auth = $auth;
  }
  
  /**
   * Instance based cache
   * @param string $type 		class type
   * @param string $name 		class subtype
   * @return object
   */
  function component($type, $name) {
    if(!isset($this->_components[$type][$name])) {
      $klass = ucfirst($type) . "_" . ucfirst($name);
      $obj = new $klass($this);
      if($type=='task') $obj = $obj->handler();
      $this->_components[$type][$name] = $obj;
    }
    return $this->_components[$type][$name];
  }
  
  function task($name) {
  	if(!isset($this->_components['task'][$name])) {
  		$klass = "Task_" . ucfirst($name);
  		$this->_components['task'][$name] = call_user_func(array($klass, "handler"), $this);
  	}
    return $this->_components['task'][$name];
  }
  
  function state($name) {
    return $this->component('state', $name);
  }
  
  function setState($state) {
    foreach($state as $name=>$handlers) {
      foreach($handlers as $handler=>$actions) {
        if(!is_array($actions)) $actions = array($actions => null);
        foreach($actions as $method=>$params) {
          $result = $this->state($handler)->$method($name, $params);
          if($result ===true ) {
            echo "{$handler} {$method} OK\n";
          } else {
            echo "{$handler} {$method} FAILED\n";
            return false;
          }
        }
      }
    }
    return true;
  }
}
