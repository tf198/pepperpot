<?php

/**
 * Tasks can contain methods that are divided into three groups
 * 
 * - **speck**:  Returns information about the system.  May be cached according to policy 
 * - **action**: Do something e.g. start a service or delete a file
 * - **state**:  Ensure a specific outcome, only performing actions as neccessary
 * 
 * @author Tris Forster
 * @package PepperPot/Task
 */
abstract class Task_Base {
	
	/**
	 * The current Minion
	 * @var Minion
	 */
	protected $minion;
	
	/**
	 * Packages which are required for the Task
	 * @var multitype:string
	 */
	protected $packages = array();
	
	/**
	 * Cache times for specks
	 * @var multitype:int
	 */
	public $cache_time = array();
	
	/**
	 * Store an instance of Task_Cmd on every Task
	 * @var Task_Cmd
	 */
	public $cmd;
	
	/**
	 * Create a new Task with a reference to the current Minion
	 * @param Minion $minion
	 */
	function __construct($minion) {
		$this->minion = $minion;
		// check the required packages are installed
		foreach($this->packages as $package) $this->minion->task('pkg')->ensure_installed($package);
		$this->cmd = ($this instanceof Task_Cmd) ? $this : $this->minion->task('cmd');
	}
	
	function cache_time($method) {
		return (isset($this->cache_time[$method])) ? $this->cache_time[$method] : Minion_Cache::CACHE_SESSION;
	}
	
	/**
	 * Get a Task instance
	 * Allows different Task classes to be loaded depending on target
	 * @param Minion $minion	target Minion
	 * @param string $klass		the called class, can drop and use static instead in PHP 5.3
	 * @return Task_Base		subclass of Task_Base
	 */
	static function handler($minion, $klass=null) {
		return new $klass($minion);
	}

}
