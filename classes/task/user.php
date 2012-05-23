<?php 
class Task_User extends Task_Base {
	public function getpw($user) {
		$user = escapeshellcmd($user);
		$line = $this->minion->task('cmd')->run("grep \"^{$user}:\" /etc/passwd");
		$parts = explode(':', $line);
		return array(
				'user' 	=> $parts[0],
				'uid' 	=> $parts[2],
				'gid'	=> $parts[3],
				'comment' => explode(',', $parts[4]),
				'home' 	=> $parts[5],
				'shell' => $parts[6],
				);
	}
	
	public function display_name($user) {
		$data = $this->minion->speck('user.getpw:' . $user);
		return $data['comment'][0];
	}
	
	public function names() {
		return $this->minion->task('cmd')->run_stdout('cut -d: -f1 /etc/passwd');
	}
	
	public function groups($user=null) {
		if($user == null) {
			return $this->minion->task('cmd')->run_stdout('cut -d: -f1 /etc/group');
		}
		
		$line = $this->minion->task('cmd')->run('groups ' . escapeshellarg($user));
		$parts = explode(' : ', $line);
		return explode(' ', $parts[1]);
	}
}
?>