<?php

/**
 * A system instance - ready to do your bidding
 */
class Minion {
  
  public $_components = array('task' => array(), 'state' => array());
  
  private $_cache = array();
  private $_cacheable = array();
  
  /**
  * @param array $params 			core params
  */
  function __construct($params) {
  	$this->_cache['core'] = $params;
  	
  	//$this->os = $this->speck('system.os');
  }
  
  /**
  * Get data from the cache
 	* @param string $key 				item key e.g. system.os
  * @return mixed 						data or null if no data available
  */
  function get($key) {
  	if(isset($this->_cache[$key])) return $this->_cache[$key];
  	return null;
  }
  
  /**
  * Set data in the cache
  * @param string $key 				item key e.g. system.os
  * @param mixed $value 			item
  * @param boolean $cacheable whether this value can be cached (default: false)
  */
  function set($key, $value, $cacheable=false) {
  	$this->_cache[$key] = $value;
  	if($cacheable) $this->_cacheable[] = $key;
  }
  
  /**
  * Specks are cachable pieces of information tied to a minion
  * They should only be used for static cacheable info
  * @param string $key				item key e.g. system.os
  * @return mixed							cached data
  */
  function speck($key) {
  	$cached = $this->get($key);
  	if($cached!==null) return $cached;
  	list($task, $func) = explode('.', $key, 2);
  	$result = $this->task($task)->$func();
  	$this->set($key, $result, true);
  	return $result;
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
