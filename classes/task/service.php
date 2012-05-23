<?php
class Task_Service extends Task_Base {
	
	private static $init_map = array(
			'arch'   => '/etc/rc.d',
			'debian' => '/etc/init.d',
			'fedora' => '/etc/init.d',
			'redhat' => '/etc/init.d',
			'ubuntu' => '/etc/init.d',
			'gentoo' => '/etc/init.d',
			'centos' => '/etc/init.d',
			'sunos'  => '/etc/init.d',
			);
	
	private $base;
	
	public function __construct($instance, $os) {
		parent::__construct($instance);
		$this->base = self::$init_map[$os];
	}
	
	/** TASKS **/
	
	public function start($name) {
		$this->run($name, 'start');
	}
	
	public function stop($name) {
		$this->run($name, 'stop');
	}
	
	public function restart($name) {
		$this->run($name, 'restart');
	}
	
	public function status($name, $binary=null) {
		
		// first try the init script
		$cmd = $this->minion->task('cmd')->run_as("{$this->base}/{$name} status", true);
		$output = $this->minion->task('cmd')->system($cmd, $ret);
		if($ret == 0) return true;
		if($ret == 4) return false; // think this is standard (?)
		if($output == "{$name} is not running") return false;
		
		// a return code of 1 is currently ambiguous
		
		// otherwise look for processes
		if(!$binary) $binary = $name;
		$procs = $this->minion->task('system')->ps($binary);
		return (count($procs) > 0);
	}
	
	public function enabled($name, $runlevel=2) {
		$cmd = sprintf("ls /etc/rc%d.d | grep -e \"S[0-9]\\+%s\\$\"", $runlevel, escapeshellcmd($name));
		return $this->minion->task('cmd')->run_ret($cmd);
	}
	
	public function enable($name) {
		$this->minion->task('cmd')->run("update-rc.d {$name} defaults", true);
	}
	
	public function disable($name) {
		$this->minion->task('cmd')->run("update-rc.d -f {$name} remove", true);
	}
	
	public function run($name, $action) {
		return $this->minion->task('cmd')->run("{$this->base}/{$name} {$action}", true);
	}
	
	/** STATES **/
	
	function ensure_running($name) {
		$service = $this->minion->task('service');
		 
		if($service->status($name)) {
			$this->minion->log("Service {$name} already running");
		} else {
			$service->start($name);
			$this->minion->log("Service {$name} started");
		}
	}
	
	function ensure_stopped($name) {
		if(!$this->status($name)) {
			$this->minion->log("Service {$name} already stopped");
		} else {
			$this->stop($name);
			$this->minion->log("Service {$name} stopped");
		}
	}
	
	function ensure_enabled($name, $level=2, $change=true) {
		if(!$this->enabled($name)) $this->enable($name);
		if($change) $this->ensure_running($name);
	}
	
	function ensure_disabled($name, $level=2, $change=true) {
		if($change) $this->ensure_stopped($name);
		if($this->enabled($name)) $this->disable($name);
	}
	
	/** IMPLEMENTATION SELECTION **/
	
	public static function handler($instance, $klass) {
		$os = $instance->speck('system.os');
		//if($os == 'ubuntu') return new Task_Upstart($instance, $os);
		
		if(isset(self::$init_map[$os])) return new Task_Service($instance, $os);
		
		throw new Task_NotImplemented("No service implementation available for '{$os}'");
	}
}