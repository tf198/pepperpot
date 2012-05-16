<?php

class Task_File extends Task_Base {

	const STAT_USER 	= 'U';
	const STAT_UID 		= 'u';
	const STAT_GROUP 	= 'G';
	const STAT_GID 		= 'g';
	const STAT_MODE		= 'a';
	const STAT_ATIME	= 'X';
	const STAT_MTIME	= 'Y';
	const STAT_CTIME	= 'Z';
	

	function md5sum($file, $elevate=false) {
		try {
			$output = $this->minion->task('cmd')->run("md5sum " . escapeshellarg($file), $elevate);
			return substr($output[0], 0, 32);
		} catch(Task_Exception $e) {
			return "";
		}
	}

	function chown($file, $owner, $group) {
		$this->minion->task('cmd')->run("chown {$owner}.{$group} " . escapeshellarg($file), true);
	}

	function mode($file) {
		return octdec("0" . $this->stat($file, self::STAT_MODE));
	}

	function owner($file) {
		return $this->stat($file, self::STAT_USER);
	}
		
	function stat($file, $attr) {
		return $this->minion->task('cmd')->run("stat -c %{$attr} " . escapeshellarg($file));
	}
}