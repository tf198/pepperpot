<?php
require_once('cmd_test.php');

class File_Test extends Cmd_Test {
	function testMode() {
		$this->cmd->session = <<< EOF
$ stat -c %a "/etc/motd"
644	
EOF;
		
		$this->assertEquals(0644, $this->minion->task('file')->mode('/etc/motd'));
	}
}