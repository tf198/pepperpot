<?php

/**
 * A system instance - ready to do your bidding
 */
class Grunt {
  
  private $_components = array('task' => array(), 'state' => array());
  
  public $params;
  
  /**
  * @param string $ip_address 			system ip
  * @param string $username 				username
  * @param string|Crypt_RSA	$auth 	password or authentication key
  */
  function __construct($params) {
  	$this->params = $params;
  }
  
  function param($name, $default=null) {
    if(isset($this->params[$name])) return $this->params[$name];
    if($default!==null) return $default;
    throw new Exception("Missing parameter: '{$name}'");
  }
  
  private function _component($type, $name) {
  	if(!isset($this->_components[$type][$name])) {
  		$klass = $type . '_' . $name;
  		$this->_components[$type][$name] = new $klass($this);
  	}
  	return $this->_components[$type][$name];
  }
  
  function task($name) {
  	if(!isset($this->_components['task'][$name])) {
  		$klass = "Task_" . ucfirst($name);
  		$this->_components['task'][$name] = call_user_func(array($klass, "handler"), $this, $klass);
  	}
    return $this->_components['task'][$name];
  }
  
  function state($name) {
  	return $this->_component('State', $name);
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
