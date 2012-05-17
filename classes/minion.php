<?php

/**
 * A system instance - ready to do your bidding
 */
class Minion {
  
  public $_components = array('task' => array(), 'state' => array());
  
  public $name;

  public $cache;
  
  // pluggable logger
  static $logger = null;
  
  /**
   * @param array $params 			core params
   */
  function __construct($name, $params, $cache=null) {
    $this->name = $name;
    $this->cache = $cache ? $cache : new Minion_Cache();
    foreach ($params as $key => $value)
      $this->cache->set('core.' . $key, $value, Minion_Cache::CACHE_PRIVATE);
  }

  function get($key, $default=null) {
    $value = $this->cache->get($key, $default);
    if($key!='core.password') $this->log("GET> {$key} = {$value}");
    return $value;
  }
  
  /**
   * Specks are cachable pieces of information tied to a minion
   * @param string $key				item key e.g. system.os
   * @return mixed							cached data
   */
  function speck($key) {
    if($this->cache->contains($key)) {
      return $this->cache->get($key);
    }
    
    $parts = explode(':', $key);
    $accessor = array_shift($parts);
    
    list($task, $func) = explode('.', $accessor, 2);
    $t = $this->task($task);
    $result = call_user_func_array(array($t, $func), $parts);
    $cache_time = isset($t->cache_time[$func]) ? $t->cache_time[$func] : Minion_Cache::CACHE_SESSION;
    $this->cache->set($key, $result, $cache_time);
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
  
  function log($message, $level=LOG_INFO) {
  	self::$logger && self::$logger->add($level, $message);
  }

}


