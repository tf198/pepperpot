<?php
class PHPSecLibTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		$f = fopen('Net/SSH2.php', 'r', true);
		if(!$f) $this->markTestSkipped("PHPSecLib not available on this system");
		fclose($f);
		
		$s = @fsockopen($GLOBALS['minion_host'], 22, $errno, $errstr, 1);
		if(!$s) $this->markTestSkipped("Unabled to contact test minion");
		fclose($s);
		
		$this->minion = new Minion(array('host' => $GLOBALS['minion_host'], 'username' => $GLOBALS['minion_user'], 'password' => $GLOBALS['minion_pass'], 'transport' => 'PHPSecLib'));
		$this->cmd = $this->minion->task('cmd');
	}
	
	function testHandler() {
		$this->assertInstanceOf('Task_PHPSecLib', $this->cmd);
	}
	
	function testSpeck() {
		$this->assertEquals('ubuntu', $this->minion->speck('system.os'));
	}
}