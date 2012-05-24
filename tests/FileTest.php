<?php
require_once('cmd_test.php');

class TaskFileTest extends Cmd_Test {
	
	/**
	 * @var Task_File
	 */
	private $file;
	
	function setUp() {
		parent::setUp();
		$this->cmd->load('ubuntu');
		$this->file = $this->minion->task('file');
	}
	
	function testMd5sum() {
		$this->cmd->session = <<< EOF
$ md5sum '/etc/motd'
6F89C2F0A719B30CC38ABDF90755F2E4  /etc/motd
EOF;
		$this->assertEquals('6F89C2F0A719B30CC38ABDF90755F2E4', $this->minion->task('file')->md5sum('/etc/motd'));
	}
	
	function testAttr() {
		$this->cmd->session = <<< EOF
$ stat -c "%Y" '/etc/motd'
12345
$ stat -c "%a" '/etc/motd'
644
$ stat -c "%U" '/etc/motd'
root
$ sudo -n chown root.admin '/etc/motd'
EOF;
		$file = $this->minion->task('file');
		$target = '/etc/motd';
		
		$this->assertEquals(12345, $file->attr($target, Task_File::STAT_MTIME));
		$this->assertEquals(0644, $file->mode($target));
		$this->assertEquals('root', $file->owner($target));
		$file->chown($target, 'root', 'admin', true);
	}
	
	function testChmod() {
		$this->cmd->session = <<< EOF
$ chmod 644 '/tmp/test.txt'
$ sudo -n chmod 644 '/tmp/test.txt'
$ sudo -n -u 'bob' chmod 644 '/tmp/test.txt'
$ chmod 755 '/tmp/test.txt'
EOF;
		$this->file->chmod('/tmp/test.txt', 0644);
		$this->file->chmod('/tmp/test.txt', 0644, true);
		$this->file->chmod('/tmp/test.txt', 420, 'bob');
		$this->file->chmod('/tmp/test.txt', '755');
	}
}