<?php

/**
 * Task for system interaction
 * @author Tris Forster
 * @package PepperPot/Task
 */
class Task_Cmd extends Task_Base {

	/**
	 * Execute a command and return the return the last line of output
	 * @pepper action
	 * @param string 		$cmd			command to execute
	 * @param	string|bool	$user			user to run as or bool (default: false)
	 * @param int 		$expected		expected return code (default: 0)
	 * @throws Task_Exception				if unexpected return code
	 * @return string						last line of output
	 */
	function run($cmd, $user=false, $expected=0) {
		if ($user) $cmd = $this->run_as($cmd, $user);

		$this->exec($cmd, $output, $ret);
		if ($ret != $expected)
			throw new Task_Exception("Cmd failed '{$cmd}' [{$ret}]: " . implode(', ', $output));
		return ($output) ? $output[count($output) - 1] : '';
	}

	/**
	 * Execute a command and return the output
	 * throws an exception if return code isn't what is expected
	 * @pepper action
	 * @param string		$cmd			command to execute
	 * @param	string|bool	$user			user to run as or bool (default: false)
	 * @param int 		$expected		expected return code (default: 0)
	 * @throws Task_Exception				if unexpected return code
	 * @return array						stdout lines
	 */
	function run_stdout($cmd, $user=false, $expected=0) {
		if ($user) $cmd = $this->run_as($cmd, $user);

		$this->exec($cmd, $output, $ret);
		if ($ret !== $expected)
			throw new Task_Exception("Cmd failed '{$cmd}' [{$ret}]: " . implode(', ', $output));
		return $output;
	}

	/**
	 * Execute a command and check the return code
	 * @pepper action
	 * @param string 		$cmd		command to execute
	 * @param	string|bool	$user			user to run as or bool (default: false)
	 * @param int			$expected	expected return code (default: 0)
	 * @return boolean				whether the return code was as expected
	 */
	function run_ret($cmd, $user=false, $expected=0) {
		if ($user) $cmd = $this->run_as($cmd, $user);
		 
		$this->system($cmd, $ret);
		return ($ret == $expected);
	}

	/**
	 * Modify command to run as a different user
	 * @param	string		$cmd	command to execute
	 * @param	string|bool	$user	false: current, true: superuser, string: specified user (default: true)
	 * @return string				modified command
	 */
	function run_as($cmd, $user=true) {
		 
		if($user === false) return $cmd;
		 
		if ($this->minion->speck('system.os') == 'windows') {
			if($user === true) $user = "Administrator";
			return sprintf("runas /User:%s %s", $this->escape($user), $cmd);
		} else {
			$sudo = "sudo -n ";
			if(is_string($user) && $user!='root') $sudo .= "-u " . $this->escape($user) . " ";
			return $sudo . $cmd;
		}
	}

	/**
	 * Emulate system() call using underlying transport
	 * @pepper action
	 * @param string $cmd	command to execute
	 * @param int	$ret	return code written to this var
	 * @return string		last line of output
	 */
	function system($cmd, &$ret) {
		$this->exec($cmd, $output, $ret);
		return ($output) ? $output[count($output) - 1] : '';
	}

	/**
	 * Emulate exec() call using underlying transport
	 * @pepper action
	 * @param string	$cmd		command to execute
	 * @param multitype:string	output written to this var
	 * @param int	$ret		return code written to this var
	 */
	function exec($cmd, &$output, &$ret) {
		$cmd .= " 2>&1";
		exec($cmd, $output, $ret);
		$this->minion->log("CMD> {$cmd} [{$ret}]");
	}

	/**
	 * Use transport implementation of stat()
	 * @pepper action
	 * @param string 		$file
	 * @param	string|bool	$user
	 * @throws Task_NotImplemented
	 * @return multitype:mixed
	 */
	function stat($file, $user=false) {
		if($user) return $this->minion->task('file')->stat($file, $user);
		return stat($file);
	}

	/**
	 * Copy a file to the remote system
	 * @pepper action
	 * @param string $local		path to local file
	 * @param string $remote	path on remote system
	 * @param int $create_mode	octal create mode for remote file
	 * @param string|bool $user
	 * @throws Task_Exception
	 */
	function copy_to($local, $remote, $create_mode=0644, $user=false) {
		$this->run(sprintf("cp %s %s", $this->escape($local), $this->escape($remote)), $user);
		if($this->minion->speck('system.kernel') == 'windows_nt') return;
		
		// set the file mode
		$this->run(sprintf("chmod %o %s", $create_mode, $this->escape($remote)), $user);
	}

	/**
	 * Copy a file from the remote system
	 * @pepper action
	 * @param string $remote		path on remote system
	 * @param string $local			path to local file
	 * @param string|bool $user
	 */
	function copy_from($remote, $local, $create_mode=0644, $user=false) {
		return $this->copy_to($remote, $local, $create_mode, $user);
	}

	/**
	 * Estimate network latency to target system
	 * @pepper speck
	 * @throws Task_Exception
	 * @return float	latency in seconds
	 */
	function latency() {
		$ts = microtime(true);
		$result = $this->cmd->system("echo Latency test", $ret);
		if($result != 'Latency test') throw new Task_Exception("Unexpected output: {$result}");
		return microtime(true) - $ts;
	}
	
	public $_ec;
	
	/**
	 * Escape shell argument for target system
	 * @param string $arg
	 * @return string
	 */
	function escape($arg) {
		if(!$this->_ec) {
			$this->_ec = ($this->minion->speck('system.kernel') == 'windows_nt') ? '"' : '\'';
		}
		return $this->_ec . str_replace($this->_ec, '\\' . $this->_ec, $arg) . $this->_ec;
	}

	static function handler($instance, $klass=null) {

		if (!$instance->get('config.local', false)) {
			$klass = function_exists('ssh2_connect') ? "Task_SSH" : "Task_PHPSecLib";
			return new $klass($instance);
		}
		return new Task_Cmd($instance);
	}

	static function construct($cmd) {
		$args = func_get_args();
		for($i=0, $c=count($args); $i<$c; $i++) {
			if(substr($args[$i], 0, 1) != '-') $args[$i] = $this->escape($args[$i]);
		}
		return implode(' ', $args);
	}

}
