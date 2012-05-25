<?php
class SSHTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		if(!extension_loaded('ssh2')) $this->markTestSkipped("SSH2 not available on this system");
		$this->minion = new Minion(array('host' => $GLOBALS['minion_host'], 'username' => $GLOBALS['minion_user'], 'password' => $GLOBALS['minion_pass']));
		$this->cmd = $this->minion->task('cmd');
	}
	
	function testHandler() {
		$minion = new Minion(array('host' => '192.168.100.41', 'username' => 'modbox', 'password' => 'xobdom'));
		$this->assertInstanceOf('Task_SSH', $this->minion->task('cmd'));
	}
}