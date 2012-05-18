<?php

/**
 * A system instance - ready to do your bidding
 */
class Minion {
  
  public $_components = array('task' => array(), 'state' => array());
  
  /**
   * Identifier for this system
   * @var string
   */
  public $name;

  /**
   * Persistance object
   * @var MinionCache
   */
  public $cache;
  
  /**
   * Pluggable logger implementing add($level, $message) method
   * @var object
   */
  static $logger = null;
  
  /**
   * @param 	string		$name	identifier used for logging
   * @param 	array 		$params core params (host, username, password etc)
   * @param 	MinionCache	$cache	optional cache object for persistance
   */
  function __construct($name, $params, $cache=null) {
    $this->name = $name;
    $this->cache = $cache ? $cache : new Minion_Cache();
    foreach ($params as $key => $value) {
      $this->cache->set('core.' . $key, $value, Minion_Cache::CACHE_PRIVATE);
  	}
  }

  /**
   * Retrieve a value from the cache
   * @param 	string	$key		
   * @param 	mixed	$default
   * @return	mixed	
   */
  function get($key, $default=null) {
    $value = $this->cache->get($key, $default);
    if($key!='core.password') $this->log("GET> {$key} = {$value}");
    return $value;
  }
  
  /**
   * Specks are cachable component results
   * @param 	string 	$key	item key e.g. task.system.os
   * @return 	mixed
   */
  function speck($key) {
    if($this->cache->contains($key)) {
      return $this->cache->get($key);
    }
    
    list($component, $method, $params) = $this->_parse_uri($key);
    $result = call_user_func_array(array($component, $method), $params);
    $cache_time = isset($component->cache_time[$method]) ? $component->cache_time[$method] : Minion_Cache::CACHE_SESSION;
    
    
    $this->cache->set($key, $result, $cache_time);
    return $result;
  }
  
  function dispatch($uri) {
  	list($component, $method, $params) = $this->_parse_uri($uri);
  	$result = call_user_func_array(array($component, $method), $params);
  	
  	return $result;
  }
  
  /**
   * Decode a string in the format <type>.<name>.<method>:arg1:arg2..
   * @param unknown_type $uri
   * @throws Exception
   * @return array		(components, method, params) 
   */
  function _parse_uri($uri) {
  	$params = explode(':', $uri);
  	$accessor = array_shift($params);
  	 
  	$parts = explode('.', $accessor);
  	if(count($parts)!=3) throw new Exception("Unexpected accessor format: {$accessor}");
  	
  	return array($this->_component($parts[0], $parts[1]), $parts[2], $params);
  }

  /**
   * Singleton factory for named components
   * @param 	string	$type	'task', 'state'
   * @param		string	$name	second part of class name
   * @return	object			component instance
   */
  private function _component($type, $name) {
    if (!isset($this->_components[$type][$name])) {
      $klass = $type . '_' . $name;
      $this->_components[$type][$name] = call_user_func(array($klass, "handler"), $this, $klass);
    }
    return $this->_components[$type][$name];
  }

  /**
   * Tasks are things to do
   * @param 	string	$name
   * @return	Task_Base	the requested task subclass
   */
  function task($name) {
    return $this->_component('task', $name);
  }

  /**
   * Desired states for the target system to be in
   * @param		string	$name
   * @return	State_Base	the requested state subclass
   */
  function state($name) {
    return $this->_component('state', $name);
  }

  /**
   * Bring a system to a state described by an array
   * $desired = array(
   * 	'state.package.installed:openssh-server',
   * 	'state.service.running:ssh',
   * )
   * @param unknown_type $state
   * @return boolean
   */
  function setState($state) {
    foreach ($state as $name => $handlers) {
      foreach ($handlers as $handler => $actions) {
        if (!is_array($actions))
          $actions = array($actions => null);
        foreach ($actions as $method => $params) {
          $result = $this->state($handler)->$method($name, $params);
          if ($result === true) {
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
  
  /**
   * Hook into a pluggable logger
   * @param		string	$message	message to log
   * @param		int		$level		one of the PHP LOG_XXXX constants
   */
  function log($message, $level=LOG_INFO) {
  	$message = sprintf("%20s: %s", $this->name, $message);
  	self::$logger && self::$logger->add($level, $message);
  }

}


