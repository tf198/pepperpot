<?php

/**
 * A system instance - ready to do your bidding
 */
class Minion {
  
  public $_components = array('task' => array(), 'state' => array());
  public $_cache = array();
  public $_expiry = array();
  
  public $name;

  /**
   * @param array $params 			core params
   */
  function __construct($name, $params) {
    $this->name = $name;
    foreach ($params as $key => $value)
      $this->_cache['core.' . $key] = $value;
  }

  /**
   * Get data from the cache
   * @param string $key item key e.g. system.os
   * @return mixed 	data or null if no data available
   */
  function get($key, $default=null) {
    if (isset($this->_cache[$key])) {
      $this->log("GET> {$key} => " . print_r($this->_cache[$key], true));
      return $this->_cache[$key];
    }
    if ($default !== null)
      return $default;
    throw new Task_Exception("Missing value for {$key}");
  }

  /**
   * Set data in the cache
   * @param string $key   item key e.g. system.os
   * @param mixed $value  item
   * @param boolean $cache_time time in seconds to cache this (default: infinate)
   */
  function set($key, $value, $cache_time=0) {
    $this->_cache[$key] = $value;
    if ($cache_time) {
      $this->_expiry[$key] = time() + $cache_time;
    }
  }
  
  /**
   * Remove a value from the cache manually
   * @param type $key 
   */
  function expire($key) {
    unset($this->_cache[$key]);
    unset($this->_expiry[$key]);
  }

  /**
   * Specks are cachable pieces of information tied to a minion
   * @param string $key				item key e.g. system.os
   * @return mixed							cached data
   */
  function speck($key) {
    if(isset($this->_cache[$key])) return $this->_cache[$key];
    
    list($task, $func) = explode('.', $key, 2);
    $t = $this->task($task);
    $result = $t->$func();
    if(isset($t->cache_time[$func])) {
      $this->set($key, $result, $t->cache_time[$func]);
    }
    return $result;
  }

  private function _component($type, $name) {
    if (!isset($this->_components[$type][$name])) {
      $klass = $type . '_' . $name;
      $this->_components[$type][$name] = new $klass($this);
    }
    return $this->_components[$type][$name];
  }

  function task($name) {
    if (!isset($this->_components['task'][$name])) {
      $klass = "Task_" . ucfirst($name);
      $this->_components['task'][$name] = call_user_func(array($klass, "handler"), $this, $klass);
    }
    return $this->_components['task'][$name];
  }

  function state($name) {
    return $this->_component('State', $name);
  }
  
  /**
   * So we can serialize minions for a basic cache
   * @return array values to serialize
   */
  function __sleep() {
    $this->_autoExpire();
    return array('_cache', '_expiry', 'name');
  }
  
  function __wakeup() {
    $this->_autoExpire();
  }
  
  function _autoExpire() {
    $now = time();
    foreach($this->_cache as $key => $value) {
      if(isset($this->_expiry[$key]) && $this->_expiry[$key]<$now) {
        $this->log('Expiring ' . $key);
        unset($this->_expiry[$key]);
        unset($this->_cache[$key]);
      }
    }
  }

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
  
  function log($message) {
    fprintf(STDERR, "%20s: %s\n", $this->name, $message);
  }

}
