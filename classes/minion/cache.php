<?php

class Minion_Cache {
  
  const CACHE_NEVER = -3;
  const CACHE_PRIVATE = -2;
  const CACHE_SESSION = -1;
  const CACHE_HOUR = 3600;
  const CACHE_DAY = 86400;
  const CACHE_INFINITE = 0;
  
  public $_cache = array();
  
  function __construct() {
    $this->now = time();
  }
  
  /**
   * Check if a key exists and is current
   * @param string $key item key e.g. system.os
   * @return boolean
   */
  function contains($key) {
    if(!isset($this->_cache[$key])) return false;
    $expiry_time = $this->_cache[$key][1];
    if($expiry_time <= 0) return true;
    if($expiry_time > $this->now) return true;
    return false;
  }
  
  /**
   * Get data from the cache, regardless of expiry time
   * @param string $key item key e.g. system.os
   * @param mixed  $default default if not set
   * @return mixed 	data or null if no data available
   * @throws Exception  if not set and no default provided
   */
  function get($key, $default=null) {
    if (isset($this->_cache[$key])) {
      return $this->_cache[$key][0];
    }
    // return default if requested
    if ($default !== null) return $default;
    
    throw new Task_Exception("Missing value for {$key}");
  }

  /**
   * Set data in the cache
   * @param string $key   item key e.g. system.os
   * @param mixed $value  item
   * @param boolean $cache_time time in seconds to cache this (default: infinate)
   */
  function set($key, $value, $cache_time=self::CACHE_SESSION) {
    if($cache_time == self::CACHE_NEVER) return;
    
    // time given in seconds from now
    if($cache_time > 0) $cache_time += time();
    
    $this->_cache[$key] = array($value, $cache_time);
  }
  /**
   * Remove an item from the cache
   * @param string $key   item key e.g. system.os
   */
  function delete($key) {
    unset($this->_cache[$key]);
  }
  
  /**
   * Remove expired items
   */
  function clean() {
    $now = time();
    foreach($this->_cache as $key => $value) {
      if($value[1]<0 || ($value[1] > 0 && $value[1]<$now)) {
        unset($this->_cache[$key]);
      }
    }
  }
  
  /**
   * Get list of all keys
   */
  function keys() {
    return array_keys($this->_cache);
  }
  
  /**
   * Allow for serialization
   * @return array
   */
  function __sleep() {
    foreach($this->_cache as $key => $value) {
      if($value[1]<self::CACHE_SESSION) unset($this->_cache[$key]);
    }
    return array('_cache');
  }
}