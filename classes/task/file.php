<?php

class Task_File extends Task_Base {

	const STAT_USER 	= '%U';
	const STAT_UID 		= '%u';
	const STAT_GROUP 	= '%G';
	const STAT_GID 		= '%g';
	const STAT_MODE		= '%a';
	const STAT_ATIME	= '%X';
	const STAT_MTIME	= '%Y';
	const STAT_CTIME	= '%Z';
	

	function md5sum($file, $elevate=false) {
		try {
			$output = $this->minion->task('cmd')->run("md5sum " . escapeshellarg($file), $elevate);
			return substr($output, 0, 32);
		} catch(Task_Exception $e) {
			return "";
		}
	}
	
	function diff($local, $remote, $brief=false, $elevate=false) {
		$local_md5 = md5_file($local);
		$remote_md5 = $this->md5sum($remote, $elevate);
		
		if($local_md5 == $remote_md5) return false;
		if($brief) return true;
		
		$tmp = tempnam(sys_get_temp_dir(), 'diff_');
		$this->minion->task('cmd')->copy_from($remote, $tmp, $elevate);
		
		$cmd = sprintf("diff -u %s %s", escapeshellarg($local), escapeshellarg($tmp));
		exec($cmd, $output, $ret);
		unlink($tmp);
	
		return $output;
	}

	function chown($file, $owner=null, $group=null, $elevate=false) {
		$o = ($owner) ? escapeshellcmd($owner) : "";
		if($group) $o .= "." . escapeshellcmd($group);
		if(!$o) throw new Task_Exception("Require at least one of owner or group");
		$this->minion->task('cmd')->run("chown {$o} " . escapeshellarg($file), $elevate);
	}
	
	function chmod($file, $mode, $elevate=false) {
		$cmd = sprintf("chmod %o %s", $mode, escapeshellarg($file));
		$this->minion->task('cmd')->run($cmd, $elevate);
	}

	function mode($file, $elevate=false) {
		return octdec("0" . $this->attr($file, self::STAT_MODE, $elevate));
	}

	function owner($file, $elevate=false) {
		return $this->attr($file, self::STAT_USER, $elevate);
	}
	
	function group($file, $elevate=false) {
		return $this->attr($file, self::STAT_GROUP, $elevate);
	}
		
	function attr($file, $attr, $elevate=false) {
		return $this->minion->task('cmd')->run("stat -c \"{$attr}\" " . escapeshellarg($file), $elevate);
	}
	
	function stat($file, $elevate=false) {
		$format = "%D %i 0 %h %u %g 0 %s %X %Y %Z %B %b";
		
		$line = $this->minion->task('cmd')->run("stat -c \"{$format}\" " . escapeshellarg($file), $elevate);
		$data = sscanf($line, "%x %d %x %d %d %d %x %d %d %d %d %d %d");
		$keys = array('dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks');
		return array_combine($keys, $data);
	}
	
	function ensure_installed($local, $remote, $elevate=false, $owner=null, $group=null, $mode=0644) {
		$changed = false;
		
		// install the file if necessary
		if($this->diff($local, $remote, true, $elevate)) {
			$this->minion->task('cmd')->copy_to($local, $remote, $mode, $elevate);
			$this->minion->log("New version of '{$remote}' installed");
			$changed = true;
		}
		
		// check the ownership
		$current = explode(' ', $this->attr($remote, "%U %G %a", $elevate));
		$o = "";
		if($owner && $owner != $current[0]) $o = escapeshellcmd($owner);
		if($group && $group != $current[1]) $o .= "." . escapeshellcmd($group);
		if($o) {
			$this->minion->task('cmd')->run("chown {$o} " . escapeshellarg($remote), $elevate);
			$changed = true;
		}
		
		// check the permissions
		if($mode != octdec($current[2])) {
			$this->chmod($remote, $mode, $elevate);
			$changed = true;
		}
		
		return $changed;
	}
}