<?php
/**
 * Service tasks using init scripts
 * @author Tris Forster
 * @package PepperPot/Task
 */
class Task_Service extends Task_Base {
	
	public $cache_time = array(
		'status' => Minion_Cache::CACHE_NEVER,
	);
	
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
	
	/**
	 * Base folder for init scripts
	 * @var string
	 */
	private $base;
	
	public static function handler($instance, $klass) {
		$os = $instance->speck('system.os');
		//if($os == 'ubuntu') return new Task_Upstart($instance, $os);
	
		if(isset(self::$init_map[$os])) return new Task_Service($instance, $os);
	
		throw new Task_NotImplemented("No service implementation available for '{$os}'");
	}
	
	public function __construct($instance, $os) {
		parent::__construct($instance);
		$this->base = self::$init_map[$os];
	}
	
	/**
	 * Start the named service
	 * @pepper action
	 * @param string $name
	 */
	public function start($name) {
		$this->run($name, 'start');
	}
	
	/**
	 * Stop the named service
	 * @pepper action
	 * @param string $name
	 */
	public function stop($name) {
		$this->run($name, 'stop');
	}
	
	/**
	 * Restart the named service
	 * @pepper action
	 * @param string $name
	 */
	public function restart($name) {
		$this->run($name, 'restart');
	}
	
	/**
	 * Determine whether the named service is running
	 * @pepper speck
	 * @param string $name		service name (as in /etc/init.d)
	 * @param string $binary    name the process will be running as e.g. sshd
	 * @return boolean			true if running
	 */
	public function status($name, $binary=null) {
		
		// first try the init script
		$cmd = $this->cmd->run_as("{$this->base}/{$name} status", true);
		$output = $this->cmd->system($cmd, $ret);
		if($ret == 0) return true;
		if($ret == 4) return false; // think this is standard (?)
		if($output == "{$name} is not running") return false;
		
		// a return code of 1 is currently ambiguous
		
		// otherwise look for processes
		if(!$binary) $binary = $name;
		$procs = $this->minion->task('system')->ps($binary);
		return (count($procs) > 0);
	}
	
	/**
	 * Determine whether the named service is enabled for the specified runlevel
	 * @pepper speck
	 * @param string $name		service name
	 * @param int $runlevel		runlevel to check
	 * @return boolean
	 */
	public function enabled($name, $runlevel=2) {
		$cmd = sprintf("ls /etc/rc%d.d | grep -e \"S[0-9]\\+%s\\$\"", $runlevel, escapeshellcmd($name));
		return $this->cmd->run_ret($cmd);
	}
	
	/**
	 * Enable the named service
	 * @pepper action
	 * @param string $name
	 */
	public function enable($name) {
		$this->cmd->run("update-rc.d {$name} defaults", true);
	}
	
	/**
	 * Disable the named service
	 * @pepper action
	 * @param service $name
	 */
	public function disable($name) {
		$this->cmd->run("update-rc.d -f {$name} remove", true);
	}
	
	/**
	 * Execute a service action
	 * @pepper action
	 * @param string $name		service name
	 * @param string $action	action (start|stop|restart|status|reload)
	 */
	public function run($name, $action) {
		return $this->cmd->run("{$this->base}/{$name} {$action}", true);
	}
	
	/**
	 * Ensure that the named service is running
	 * @pepper state
	 * @param string $name
	 */
	function ensure_running($name) {
		if($service->status($name)) {
			$this->minion->log("Service {$name} already running");
		} else {
			$this->start($name);
			$this->minion->log("Service {$name} started");
		}
	}
	
	/**
	 * Ensure that the named service is stopped
	 * @pepper state
	 * @param string $name
	 */
	function ensure_stopped($name) {
		if(!$this->status($name)) {
			$this->minion->log("Service {$name} already stopped");
		} else {
			$this->stop($name);
			$this->minion->log("Service {$name} stopped");
		}
	}
	
	/**
	 * Ensure the named service is enabled
	 * @pepper state
	 * @param string $name		service name
	 * @param boolean $change	whether to start the service after enabling (default: true)
	 */
	function ensure_enabled($name, $change=true) {
		if(!$this->enabled($name)) $this->enable($name);
		if($change) $this->ensure_running($name);
	}
	
	/**
	 * Ensure the named service is disabled
	 * @pepper state
	 * @param string $name		service name
	 * @param boolean $change	whether to stop the service before disabling (default: true)
	 */
	function ensure_disabled($name, $change=true) {
		if($change) $this->ensure_stopped($name);
		if($this->enabled($name)) $this->disable($name);
	}
}