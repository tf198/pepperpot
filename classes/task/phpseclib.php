<?php

$phpseclib = realpath(dirname(__FILE__) . "/../../") . "/vendor/phpseclib";
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclib);

class Task_PHPSecLib extends Task_Cmd {
	const BOUNDARY = '==jv8dvngvn94d=';

	private $ssh, $sftp;

	public $stderr;

	function _connect($type) {
		$addr = $this->minion->get('config.host');
		$port = $this->minion->get('config.port', 22);
		$user = $this->minion->get('config.username');
		
		$keyfile = $this->minion->get('config.keyfile', false);
		if ($keyfile) {
			require_once "Crypt/RSA.php";
			$auth = new Crypt_RSA();
			$auth->loadKey(file_get_contents($keyfile));
		} else {
			$auth = $this->minion->get('config.password');
		}
		
		require_once "Net/{$type}.php";
		$klass = "Net_{$type}";
		$transport = new $klass($addr, $port);
		$this->minion->log("{$type}> connect {$user}@{$addr} (port {$port})");
		if(!$transport->login($user, $auth)) {
			throw new Task_Exception("Authentication failed for {$addr}");
		}
		return $transport;
	}

	function exec($cmd, &$output, &$ret) {
		if(!$this->ssh) $this->ssh = $this->_connect('SSH2');

		$ret_cmd = $cmd . '; echo __$?__';
		$output = explode("\n", trim($this->ssh->exec($ret_cmd)));
		// stderr is after result code
		$result = array_pop($output);
		$this->stderr = array();
		$ret = -1;
		while(sscanf($result, "__%d__", $ret) != 1 && $output) {
			$this->stderr[] = $result;
			$result = array_pop($output);
		}
		$this->minion->log("SSH> {$cmd} [{$ret}]");
	}

	function stat($file, $user=false) {
		if($user) return parent::stat($file, $user);
		 
		if(!$this->sftp) $this->sftp = $this->_sftp();
		 
		return $this->sftp->stat($file);
	}

	function copy_to($local, $remote, $mode=0644, $user=false) {
		if(!$this->sftp) $this->sftp = $this->_connect('SFTP');

		if ($user) {
			$dest = "/tmp/" . str_replace('/', '_', $remote);
		} else {
			$dest = $remote;
		}

		$data = file_get_contents($local);
		if(!$this->sftp->put($dest, $data))
			throw new Task_Exception("Failed to send file: 'local:{$local}' > 'remote:{$dest}' [" . $this->sftp->getLastSFTPError() . ']');
		$this->minion->log("SFTP> 'local:{$local}' > 'remote:{$dest}'");
		if($user) {
			$this->run("mv \"{$dest}\" \"{$remote}\"", $user);
		}
		return true;
	}

	function copy_from($remote, $local, $mode=0644, $user=false) {
		if(!$this->sftp) $this->sftp = $this->_connect('SFTP');
		if ($user) {
			throw new Task_NotImplemented("Not implemented");
		}

		if(!$this->sftp->get($remote, $local))
			throw new Task_Exception("Failed to receive file: '{$remote}' > '{$local}'");
		$this->minion->log("SFTP> 'remote:{$remote}' > 'local:{$local}'");
		return true;
	}

}
