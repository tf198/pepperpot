<?php
require_once('cmd_test.php');

class File_Test extends Cmd_Test {
	
	function setUp() {
		parent::setUp();
		$this->cmd->load('ubuntu');
	}
	
	function testMd5sum() {
		$this->cmd->session = <<< EOF
$ md5sum "/etc/motd"
6F89C2F0A719B30CC38ABDF90755F2E4  /etc/motd
EOF;
		$this->assertEquals('6F89C2F0A719B30CC38ABDF90755F2E4', $this->minion->task('file')->md5sum('/etc/motd'));
	}
	
	function testStat() {
		$this->cmd->session = <<< EOF
$ stat -c %Y "/etc/motd"
12345
$ stat -c %a "/etc/motd"
644
$ stat -c %U "/etc/motd"
root
$ sudo -n chown root.admin "/etc/motd"
EOF;
		$file = $this->minion->task('file');
		$target = '/etc/motd';
		
		$this->assertEquals(12345, $file->stat($target, Task_File::STAT_MTIME));
		$this->assertEquals(0644, $file->mode($target));
		$this->assertEquals('root', $file->owner($target));
		$file->chown($target, 'root', 'admin');
	}
}