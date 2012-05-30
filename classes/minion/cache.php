<?php
/**
 *
 * @author tris
 * @package PepperPot
 */
class Minion_Cache {

	const CACHE_FLAG		= -6;
	const CACHE_RETURN		= -5;
	const CACHE_MISSING 	= -4;
	const CACHE_NEVER 		= -3;
	const CACHE_PRIVATE 	= -2;
	const CACHE_SESSION 	= -1;
	const CACHE_HOUR 		= 3600;
	const CACHE_DAY 		= 86400;
	const CACHE_INFINITE 	= 0;

	/**
	 * @var multitype:array
	 */
	public $_cache;

	function __construct($data=array()) {
		$this->now = time();
		$this->_cache = $data;
	}

	/**
	 * Check if a key exists and is current
	 * @param string $key item key e.g. system.os
	 * @return boolean
	 */
	function contains($key) {
		if(!isset($this->_cache[$key])) return false;
		$expiry_time = $this->_cache[$key][1];
		// FLAG values are always expired
		if($expiry_time == self::CACHE_FLAG) return false;
		// other special values are always current
		if($expiry_time <= 0) return true;
		// check the timestamp
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
	 * @param int $expiry_time timestamp or Minion_Cache::CACHE_X constant
	 */
	function set($key, $value, $expiry_time=self::CACHE_SESSION) {
		if($expiry_time == self::CACHE_NEVER) return;
		if($expiry_time > 0) $expiry_time += time();
		$this->_cache[$key] = array($value, $expiry_time);
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
	 * Retains active, CACHE_PRIVATE and CACHE_INFINITE items
	 */
	function clean() {
		$now = time();
		foreach($this->_cache as $key => $value) {
			// retain PRIVATE values
			if($value[1] == self::CACHE_PRIVATE) continue;
			// remove all other special values
			if($value[1] < 0) unset($this->_cache[$key]);
			// check timestamp for all positive values
			if($value[1] > 0 && $value[1] < $now) unset($this->_cache[$key]);
		}
	}

	/**
	 * Get a copy of the cache with all the CACHE_PRIVATE data removed
	 * @return	multitype:array
	 */
	function data($type=self::CACHE_SESSION) {
		$result = array();
		foreach($this->_cache as $key=>$value) {
			if($value[1] >= $type) $result[$key] = $value;
		}
		return $result;
	}

	/**
	 * Get list of all keys
	 * @return multitype:string
	 */
	function keys() {
		return array_keys($this->_cache);
	}
}