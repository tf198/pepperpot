<?php
/**
 * File operation tasks
 * @author Tris Forster
 * @package PepperPot/Task
 */
class Task_File extends Task_Base {

	const STAT_USER 	= '%U';
	const STAT_UID 		= '%u';
	const STAT_GROUP 	= '%G';
	const STAT_GID 		= '%g';
	const STAT_MODE		= '%a';
	const STAT_ATIME	= '%X';
	const STAT_MTIME	= '%Y';
	const STAT_CTIME	= '%Z';
	

	/**
	 * Get md5 for remote file
	 * @pepper speck
	 * @param string $file
	 * @param string|boolean $user	run as user (default: false)
	 * @return string
	 */
	function md5sum($file, $user=false) {
		try {
			$output = $this->minion->task('cmd')->run("md5sum " . escapeshellarg($file), $user);
			return substr($output, 0, 32);
		} catch(Task_Exception $e) {
			return "";
		}
	}
	
	/**
	 * Compare a local file to a remote file
	 * @pepper speck
	 * @param string $local					local file path
	 * @param string $remote				remote file path
	 * @param boolean $brief				only determine if files differ (default: false) 
	 * @param string|bool $user				run as user (default: false)
	 * @return boolean|multitype:string		unified diff output
	 */
	function diff($local, $remote, $brief=false, $user=false) {
		$local_md5 = md5_file($local);
		$remote_md5 = $this->md5sum($remote, $user);
		
		if($local_md5 == $remote_md5) return false;
		if($brief) return true;
		
		$tmp = tempnam(sys_get_temp_dir(), 'diff_');
		$this->minion->task('cmd')->copy_from($remote, $tmp, $user);
		
		$cmd = sprintf("diff -u %s %s", escapeshellarg($local), escapeshellarg($tmp));
		exec($cmd, $output, $ret);
		unlink($tmp);
	
		return $output;
	}

	/**
	 * Change file ownership
	 * @pepper action
	 * @param string $file			remote file path
	 * @param string $owner			user name (default: null - no change)
	 * @param string $group			group name (default: null - no change)
	 * @param string|bool $user		run as user (default: false)
	 * @throws Task_Exception		if neither $owner or $group are specified
	 */
	function chown($file, $owner=null, $group=null, $user=false) {
		$o = ($owner) ? escapeshellcmd($owner) : "";
		if($group) $o .= "." . escapeshellcmd($group);
		if(!$o) throw new Task_Exception("Require at least one of owner or group");
		$this->minion->task('cmd')->run("chown {$o} " . escapeshellarg($file), $user);
	}
	
	/**
	 * Change file permissions
	 * @pepper action
	 * @param string $file			remote file path
	 * @param int $mode				octal permissions e.g. 0644
	 * @param string|bool $user		run as user (default: false)
	 */
	function chmod($file, $mode, $user=false) {
		$cmd = sprintf("chmod %o %s", $mode, escapeshellarg($file));
		$this->minion->task('cmd')->run($cmd, $user);
	}

	/**
	 * Get file permission mode
	 * @pepper speck
	 * @param string $file			remote file path
	 * @param string|bool $user		run as user (default: false)
	 * @return int
	 */
	function mode($file, $user=false) {
		return octdec("0" . $this->attr($file, self::STAT_MODE, $user));
	}
	
	/**
	 * Get file owner
	 * @pepper speck
	 * @param string $file			remote file path
	 * @param string|bool $user		run as user (default: false)
	 * @return string
	 */
	function owner($file, $user=false) {
		return $this->attr($file, self::STAT_USER, $user);
	}
	
	/**
	 * Get file group
	 * @pepper speck
	 * @param string $file			remote file path
	 * @param string|bool $user		run as user (default: false)
	 * @return string
	 */
	function group($file, $user=false) {
		return $this->attr($file, self::STAT_GROUP, $user);
	}
	
	/**
	 * Get file attribute
	 * @pepper speck
	 * @param string $file			remote file path
	 * @param int $attr				one of the Task_File::STAT_X constants
	 * @param string|bool $user		run as user (default: false)
	 * @return string
	 */
	function attr($file, $attr, $user=false) {
		return $this->minion->task('cmd')->run("stat -c \"{$attr}\" " . escapeshellarg($file), $user);
	}
	
	/**
	 * Generic stat() call
	 * @pepper speck
	 * @param string $file			remote file path
	 * @param string|bool $user		run as user (default: false)
	 * @return multitype:string
	 */
	function stat($file, $user=false) {
		$format = "%D %i 0 %h %u %g 0 %s %X %Y %Z %B %b";
		
		$line = $this->minion->task('cmd')->run("stat -c \"{$format}\" " . escapeshellarg($file), $user);
		$data = sscanf($line, "%x %d %x %d %d %d %x %d %d %d %d %d %d");
		$keys = array('dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks');
		return array_combine($keys, $data);
	}
	
	/**
	 * Ensure a local file exists and is identical on a remote system
	 * @pepper state
	 * @param string $local			local file path
	 * @param string $remote		remote file path
	 * @param string|bool $user		run as user (default: false)
	 * @param string $owner			user name (default: null - run as user)
	 * @param string $group			group name (default: null - run as group)
	 * @param int $mode				octal file permissions
	 */
	function ensure_installed($local, $remote, $user=false, $owner=null, $group=null, $mode=0644) {
		$changed = false;
		
		// install the file if necessary
		if($this->diff($local, $remote, true, $user)) {
			$this->minion->task('cmd')->copy_to($local, $remote, $mode, $user);
			$this->minion->log("New version of '{$remote}' installed");
			$changed = true;
		}
		
		// check the ownership
		$current = explode(' ', $this->attr($remote, "%U %G %a", $user));
		$o = "";
		if($owner && $owner != $current[0]) $o = escapeshellcmd($owner);
		if($group && $group != $current[1]) $o .= "." . escapeshellcmd($group);
		if($o) {
			$this->minion->task('cmd')->run("chown {$o} " . escapeshellarg($remote), $user);
			$changed = true;
		}
		
		// check the permissions
		if($mode != octdec($current[2])) {
			$this->chmod($remote, $mode, $user);
			$changed = true;
		}
		
		return $changed;
	}
}