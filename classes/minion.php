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
   * Pluggable logger implementing add($level, $message) method
   * @var object
   */
  static $logger = null;
  
  /**
   * @param 	string				$name	identifier used for logging
   * @param 	multitype:string	$config core params (host, username, password etc)
   * @param 	MinionCache			$cache	optional cache object for persistance
   */
  function __construct($name, $config, $cache=null) {
    $this->name = $name;
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
    $this->log("GET> {$key} = " . var_export($filtered, true));
    return $value;
  }
  
  /**
   * Get a **speck** by key
   * @param 	string 	$key	item key e.g. system.os
   * @param		boolean	$ignore_cache	ignore any cached value
   * @return 	mixed
   */
  function speck($key, $ignore_cache=false) {
    if($this->cache->contains($key) && !$ignore_cache) {
      return $this->cache->get($key);
    }
    
    list($task, $method, $params) = self::parse_uri($key);
    $t = $this->task($task);
    $result = call_user_func_array(array($t, $method), $params);
    $expiry = isset($t->cache_time[$method]) ? $t->cache_time[$method] : Minion_Cache::CACHE_SESSION;
    if($expiry > 0) $expiry += time();
    
    $this->cache->set($key, $result, $expiry);
    return $result;
  }
  
  /**
   * Call a **state** or an **action** method by key
   * Stores the current timestamp 
   * @param string	$uri
   * @return mixed
   */
  function invoke($key) {
  	list($task, $method, $params) = self::parse_uri($key);
    $t = $this->task($task);
    $result = call_user_func_array(array($t, $method), $params);
    
    
    $this->cache->set($key, $result, time());
    return $result;
  }
  
  /**
   * Get the time of the last invoke(key) call
   * @param string $key
   * @return int				time of last run or 0 for unknown
   */
  function timestamp($key) {
  	$expires = $this->cache->get_expiry($key);
  	if($expires < 0) $expires = 0;
  	return $expires;
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
  	$message = sprintf("%20s: %s", $this->name, $message);
  	self::$logger && self::$logger->add($level, $message);
  }

}


