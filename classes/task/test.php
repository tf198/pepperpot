<?

class Task_Test extends Task_Base {
	function verify() {
		$result = $this->grunt->task('cmd')->run_stdout('whoami', true);
		var_dump($result);
		return ($result[0] == 'root');
	}
}
