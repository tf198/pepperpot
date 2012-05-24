<?php
/**
 * Basic information about a system
 * @author Tris Forster
 * @package PepperPot/Task
 */
class Task_System extends Task_Base {
	public $cache_time = array(
			'os' => 0, 
			'kernel' => 0, 
			'latency' => 60, 
			'hostname' => Minion_Cache::CACHE_INFINITE,
			'cpuinfo' => Minion_Cache::CACHE_INFINITE,
			'cpu' => Minion_Cache::CACHE_INFINITE,
			'memory' => Minion_Cache::CACHE_INFINITE, 
			'info' => Minion_Cache::CACHE_NEVER, // avoid duplication
		);

	/**
	 * Get system os
	 * @pepper speck
	 * @return string		e.g. windows, ubuntu, redhat, centos
	 */
	function os() {
		if($this->minion->speck('system.kernel')=='windows_nt') return 'windows';

		// ubuntu - a bit tricky actually
		$this->cmd->system('test -f /etc/issue.net', $ret);
		if($ret==0) {
			$version = $this->cmd->run('cat /etc/issue.net');
			if(substr($version, 0, 6) == 'Ubuntu') return 'ubuntu';
		}

		return "unknown";
	}

	/**
	 * Get system kernel
	 * @pepper speck
	 * @return string		e.g. windows_nt, linux
	 */
	function kernel() {
		$env = $this->cmd->run('echo %OS%');
		if($env != "%OS%") return strtolower($env);

		return strtolower($this->cmd->run('uname -s'));
	}

	/**
	 * Get kernel version
	 * @throws Task_Exception
	 * @throws Task_NotImplemented
	 */
	function kernel_version() {
		switch($this->minion->speck('system.kernel')) {
			case 'linux':
				return $this->cmd->run('uname -r');
			case 'windows_nt':
				$data = $this->cmd->run('cmd /c ver');
				if(!preg_match('/^Microsoft Windows (.*)\[Version ([0-9\.]+)\]$/', $data, $matches)) throw new Task_Exception("Failed to parse version: {$data}");
				return $matches[2];
			default:
				throw new Task_NotImplemented();
		}
	}

	/**
	 * Get raw CPU information
	 * @pepper speck
	 * @throws Task_NotImplemented
	 * @return multitype:string
	 */
	function cpuinfo() {
		switch($this->minion->speck('system.kernel')) {
			case 'linux':
				$data = $this->cmd->run_stdout('cat /proc/cpuinfo');
				$raw = $this->_parse_keypairs($data, ':');
				return $raw;
				
			case 'windows_nt':
				$data = $this->cmd->run_stdout('wmic cpu');
				$len = strlen($data[0]);
				$start = 0;
				$raw = array();
				for($i=0; $i<$len-1; $i++) {
					if($data[0][$i]==' ' && $data[0][$i+1]!=' ') {
						$key = trim(substr($data[0], $start, $i-$start));
						$raw[$key] = trim(substr($data[1], $start, $i-$start));
						$start = $i;
					}
				}
				return $raw;
				
				
			default:
				throw new Task_NotImplemented;
		}
	}
	
	/**
	 * Get standardised CPU information
	 * pepper speck
	 * @throws Task_NotImplemented
	 * @return multitype:string		vendor, model, MHz, bogomips
	 */
	function cpu() {
		$raw = $this->cpuinfo();
		switch($this->minion->speck('system.kernel')) {
			case 'linux':
				return array(
						'vendor' => $raw['vendor_id'],
						'model' => $raw['model name'],
						'MHz' => $raw['cpu MHz'],
						'bogomips' => $raw['bogomips'],
				);
			case 'windows_nt':
				return array(
						'vendor' => $raw['Manufacturer'],
						'model' => $raw['Name'],
						'MHz' => $raw['CurrentClockSpeed'],
						'bogomips' => null,
				);
			default:
				throw new Task_NotImplemented;
		}
	}
	
	/**
	 * Get standardised memory info (KB)
	 * @throws Task_NotImplemented
	 * @return multitype:string			total, free
	 */
	function meminfo() {
		switch($this->minion->speck('system.kernel')) {
			case 'linux':
				$data = $this->cmd->run_stdout('head -n 5 /proc/meminfo');
				$raw = array();
				foreach($data as $line) {
					list($key, $value) = explode(':', $line, 2);
					$raw[$key] = substr(trim($value), 0, -3);
				}
				return array(
						'total' => $raw['MemTotal']/1024,
						'free' => $raw['MemFree']/1024,
				);
			default:
				throw new Task_NotImplemented;
		}
	}
	
	/**
	 * Get installed memory (KB)
	 * @return int
	 */
	function memory() {
		$data = $this->meminfo();
		return $data['total'];
	}

	/**
	 * Parse output based on separator
	 * Trims keys and values
	 * @param array $data		output lines
	 * @param string $sep		separator (default: =)
	 * @return multitype:string	key/value pairs
	 */
	function _parse_keypairs($data, $sep='=') {
		$result = array();
		foreach($data as $line) {
			$parts = explode($sep, $line, 2);
			$key = trim($parts[0]);
			if($key) $result[$key] = trim($parts[1]);
		}
		return $result;
	}

	/**
	 * Get system hostname
	 * @pepper speck
	 * @return string
	 */
	function hostname() {
		// same for everything I think
		return $this->cmd->run('hostname');
	}

	/**
	 * Get system timestamp
	 * @throws Task_NotImplemented
	 * @return int		unix timestamp
	 */
	function time() {
		switch($this->minion->speck('system.kernel')) {
			case 'linux':
				$date = $this->cmd->run('date -R');
				return strtotime($date);
			default:
				throw new Task_NotImplemented();
		}
	}
	
	/**
	 * Get uptime information
	 * @pepper speck
	 * @throws Task_Exception
	 * @return multitype:string
	 */
	function uptime() {
		$data = $this->cmd->run('uptime');
		if(!preg_match('/up\s+([0-9\:]+),\s+(\d+) users?,\s+load average: ([0-9\.]+), ([0-9\.]+), ([0-9\.]+)$/', $data, $matches)) {
			throw new Task_Exception("Failed to parse uptime");
		}
		return array(
				'up' => $matches[1],
				'users' => $matches[2],
				'load' => array_slice($matches, 3),
				);
	}

	/**
	 * Calculate time offset from local machine
	 * @pepper speck
	 * @return int	timestamp
	 */
	function time_offset() {
		return time() - $this->time();
	}

	/**
	 * Manually expire a key
	 * @deprecated
	 * @pepper action
	 * @param string $key
	 */
	function expire($key) {
		return $this->minion->expire($key);
	}
	
	/**
	 * Forced caching of static system parameters
	 * @return multitype:string
	 */
	function info() {
		return array(
				'kernel' => $this->minion->speck('system.kernel'),
				'os' => $this->minion->speck('system.os'),
				'cpu' => $this->minion->speck('system.cpu'),
				'memory' => $this->minion->speck('system.memory'),
		);
	}
	
	/**
	 * Get process ids
	 * @pepper speck
	 * @param string $name		process name e.g. sshd
	 * @return multitype:int
	 */
	function ps($name) {
		$this->cmd->exec('ps -C ' . $this->cmd->escape($name), $output, $ret);
		$result = array();
		for($i=1, $c=count($output); $i<$c; $i++) {
			$result[] = (int)$output[$i];
		}
		return $result;
	}
}
?>
