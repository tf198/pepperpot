<?php

/**
 * A system instance - ready to do your bidding
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
   * @var MinionCache
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
   * Specks are cachable task results
   * @param 	string 	$key	item key e.g. system.os
   * @return 	mixed
   */
  function speck($key) {
    if($this->cache->contains($key)) {
      return $this->cache->get($key);
    }
    
    list($task, $method, $params) = self::parse_uri($key);
    $t = $this->task($task);
    $result = call_user_func_array(array($t, $method), $params);
    $cache_time = isset($t->cache_time[$method]) ? $t->cache_time[$method] : Minion_Cache::CACHE_SESSION;
    
    
    $this->cache->set($key, $result, $cache_time);
    return $result;
  }
  
  /**
   * Dispatch a uri in the same format as speck() but without caching it
   * @param string	$uri
   * @return mixed
   */
  function dispatch($uri) {
  	list($task, $method, $params) = self::parse_uri($uri);
  	$result = call_user_func_array(array($this->task($task), $method), $params);
  	
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
  	$message = sprintf("%20s: %s", $this->name, $message);
  	self::$logger && self::$logger->add($level, $message);
  }

}


