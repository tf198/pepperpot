<?php
require_once('cmd_test.php');

class LocalTest extends Cmd_Test {
	
	function testExec() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ command1
$ command2
one
two
three
$ badcommand
Bad command
$?=1
EOF;
		$this->cmd->exec('command1', $output, $ret);
		$this->assertEquals(0, $ret);
		$this->assertEquals(array(), $output);
		
		$this->cmd->exec('command2', $output, $ret);
		$this->assertEquals(0, $ret);
		$this->assertEquals(array('one', 'two', 'three'), $output);
		
		$this->cmd->exec('badcommand', $output, $ret);
		$this->assertEquals(1, $ret);
		$this->assertEquals(array('Bad command'), $output);
	}
	
	function testSystem() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ command1
$ command2
one
$ command3
one
two
three
$ badcommand
Bad command
$?=1
EOF;
		$output = $this->cmd->system('command1', $ret);
		$this->assertEquals(0, $ret);
		$this->assertEquals('', $output);
		
		$output = $this->cmd->system('command2', $ret);
		$this->assertEquals(0, $ret);
		$this->assertEquals('one', $output);
		
		$output = $this->cmd->system('command3', $ret);
		$this->assertEquals(0, $ret);
		$this->assertEquals('three', $output);
		
		$output = $this->cmd->system('badcommand', $ret);
		$this->assertEquals(1, $ret);
		$this->assertEquals('Bad command', $output);
	}
	
	function testRunAs() {
		$this->cmd->load('ubuntu');
		$this->assertEquals('sudo -n mycommand', $this->cmd->run_as('mycommand'));
		$this->assertEquals('mycommand', $this->cmd->run_as('mycommand', false));
		$this->assertEquals('sudo -n mycommand', $this->cmd->run_as('mycommand', true));
		$this->assertEquals('sudo -n -u bob mycommand', $this->cmd->run_as('mycommand', 'bob'));
		$this->assertEquals('sudo -n mycommand', $this->cmd->run_as('mycommand', 'root'));
		$this->assertEquals('sudo -n mycommand', $this->cmd->run_as('mycommand', 1));
	}
	
	function testRun() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ command1
$ sudo -n command1
$ sudo -n -u bob command1
$ command2
one
$ command3
one
two
three
$ badcommand
Bad command
$?=1
$ badcommand
Bad command
$?=1
EOF;
		$this->assertEquals('', $this->cmd->run('command1'));
		$this->assertEquals('', $this->cmd->run('command1', true));
		$this->assertEquals('', $this->cmd->run('command1', 'bob'));
		
		$this->assertEquals('one', $this->cmd->run('command2'));
		$this->assertEquals('three', $this->cmd->run('command3'));
		
		try {
			$this->cmd->run('badcommand');
			$this->fail('Command should have failed');
		} catch(Task_Exception $e) {
			$this->assertEquals("Cmd failed 'badcommand' [1]: Bad command", $e->getMessage());
		}
		
		$this->assertEquals('Bad command', $this->cmd->run('badcommand', false, 1));
	}
	
	function testRunStdOut() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ command1
$ command2
one
two
three
$ sudo -n command3
$ sudo -n -u bob command4
$ badcommand
$?=1
$ badcommand
$?=1
EOF;
		$this->assertEquals(array(), $this->cmd->run_stdout('command1'));
		$this->assertEquals(array('one', 'two', 'three'), $this->cmd->run_stdout('command2'));
		
		$this->assertEquals(array(), $this->cmd->run_stdout('command3', true));
		$this->assertEquals(array(), $this->cmd->run_stdout('command4', 'bob'));
		
		try {
			$this->cmd->run_stdout('badcommand');
			$this->fail('Command should have failed');
		} catch(Task_Exception $e) {
			$this->assertEquals("Cmd failed 'badcommand' [1]: ", $e->getMessage());
		}
		
		$this->assertEquals(array(), $this->cmd->run_stdout('badcommand', false, 1));
	}
	
	function testCopyTo() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ cp "local/test1" "remote/test1"
$ chmod 644 "remote/test1"
$ cp "local/test1" "remote/test1"
$ chmod 755 "remote/test1"
$ sudo -n cp "local/test1" "remote/test1"
$ sudo -n chmod 644 "remote/test1"
$ sudo -n -u bob cp "local/test1" "remote/test1"
$ sudo -n -u bob chmod 644 "remote/test1"
EOF;
		$this->cmd->copy_to('local/test1', 'remote/test1');
		$this->cmd->copy_to('local/test1', 'remote/test1', 0755);
		$this->cmd->copy_to('local/test1', 'remote/test1', 0644, true);
		$this->cmd->copy_to('local/test1', 'remote/test1', 0644, 'bob');
	}
		
	function testCopyFrom() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ cp "remote/test2" "local/test2"
$ chmod 644 "local/test2"
$ cp "remote/test2" "local/test2"
$ chmod 755 "local/test2"
$ sudo -n cp "remote/test2" "local/test2"
$ sudo -n chmod 644 "local/test2"
$ sudo -n -u bob cp "remote/test2" "local/test2"
$ sudo -n -u bob chmod 644 "local/test2"
EOF;
		$this->cmd->copy_from('remote/test2', 'local/test2');
		$this->cmd->copy_from('remote/test2', 'local/test2', 0755);
		$this->cmd->copy_from('remote/test2', 'local/test2', 0644, true);
		$this->cmd->copy_from('remote/test2', 'local/test2', 0644, 'bob');
	}
}