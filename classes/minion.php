<?php

/**
 * A system instance - ready to do your bidding
 * @package PepperPot
 */
class Minion {
  
  /**
   * Singleton store for Task_Base subclasses
   * @var multitype:Task_Base
   */
  public $_tasks = array();
  
  /**
   * Identifier for this system
   * @var string
   */
  public $name;

  /**
   * Persistance object
   * @var Minion_Cache
   */
  public $cache;
  
  /**
   * Pluggable logger implementing log($message, $level) method
   * @var object
   */
  static $logger = null;
  
  /**
   * @param 	multitype:string	$config core params (host, username, password etc)
   * @param 	MinionCache			$cache	optional cache object for persistance
   */
  function __construct($config, $cache=null) {
    $this->name = isset($config['id']) ? $config['id'] : 'Unknown';
    $this->cache = $cache ? $cache : new Minion_Cache();
    foreach ($config as $key => $value) {
      $this->cache->set('config.' . $key, $value, Minion_Cache::CACHE_PRIVATE);
  	}
  }
  
  /**
   * Generate a key, taking care to escape arguments
   * @param string $accessor
   * @param string $arg1
   * @param string $arg2
   * @return string		'task.func:arg1:arg2'
   */
  static function key($accessor) {
  	$args = func_get_args();
  	foreach($args as &$arg) $arg = str_replace(':', '\\:', (string)$arg);
  	return implode(':', $args);
  }

  /**
   * Retrieve a value from the cache
   * @param 	string	$key		
   * @param 	mixed	$default
   * @return	mixed	
   */
  function get($key, $default=null) {
    $value = $this->cache->get($key, $default);
    $filtered = ($key!='config.password') ? $value : '********';
    $this->log("GET> {$key} = " . var_export($filtered, true), LOG_DEBUG);
    return $value;
  }
  
  /**
   * Get a **speck** by key
   * @param 	string 	$key	item key e.g. system.os
   * @param		boolean	$ignore_cache	ignore any cached value
   * @return 	mixed
   */
  function speck($key, $ignore_cache=false) {
  	// return from cache if appropriate
    if($this->cache->contains($key) && !$ignore_cache) {
      $this->log("speck({$key}): (cached)", LOG_DEBUG);
      return $this->cache->get($key);
    }
    
    // execute the task
    list($task, $method, $params) = self::parse_uri($key);
    $t = $this->task($task);
    $result = call_user_func_array(array($t, $method), $params);
    
    // determine cache time for the task
    $expiry = $t->cache_time($method);
    
    // allow tasks to set their own expiry time
    if($expiry == Minion_Cache::CACHE_RETURN) $expiry = (int) $result;
    
    // store for future use
    $this->cache->set($key, $result, $expiry);
    $this->log("speck({$key}): {$expiry}", LOG_DEBUG);
    return $result;
  }
  
  /**
   * Decode a string in the format <name>.<method>:arg1:arg2..
   * Args can escape : with \:
   * @param string $uri
   * @throws Exception
   * @return multitype:mixed
   */
  static function parse_uri($uri) {
  	$uri = str_replace('\\:', '%3A', $uri);
  	
  	$params = explode(':', $uri);
  	$accessor = array_shift($params);
  	
  	$params = array_map('urldecode', $params);
  	 
  	$parts = explode('.', $accessor);
  	if(count($parts)!=2) throw new Exception("Unexpected accessor format: {$accessor}");
  	
  	return array($parts[0], $parts[1], $params);
  }

  /**
   * Singleton factory for Tasks
   * @param 	string	$name
   * @return	Task_Base	the requested task subclass
   */
  function task($name) {
    if (!isset($this->_tasks[$name])) {
      $klass = 'Task_' . $name;
      if(!class_exists($klass)) throw new Exception("No such task: " . $name);
      $this->_tasks[$name] = call_user_func(array($klass, "handler"), $this, $klass);
    }
    return $this->_tasks[$name];
  }
  
  /**
   * Hook into a pluggable logger
   * @param		string	$message	message to log
   * @param		int		$level		one of the PHP LOG_XXXX constants
   */
  function log($message, $level=LOG_INFO) {
  	if(self::$logger) {
  		$message = sprintf("%s: %s", $this->name, $message);
  		self::$logger->log($message, $level);
  	}
  }

}


