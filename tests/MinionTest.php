<?php
require_once('cmd_test.php');

class MinionTest extends PHPUnit_Framework_TestCase {
	
	function testParseURI() {
		$this->assertEquals(array('system', 'os', array('one', 'two', 'thre:e')), Minion::parse_uri('system.os:one:two:thre\\:e'));
	}
	
	function testKey() {
		$this->assertEquals('task.func', Minion::key('task.func'));
		$this->assertEquals('task.func:one', Minion::key('task.func', 'one'));
		$this->assertEquals('task.func:one:2', Minion::key('task.func', 'one', 2));
		$this->assertEquals('task.func:first\\:arg:second\\:\\:arg', Minion::key('task.func', 'first:arg', 'second::arg'));
	}
	
	function testEscape() {
		$minion = new Minion(array('local' => true));
		$minion->cache->set('system.kernel', 'linux', 0);
		
		$cmd = $minion->task('cmd');
		
		$this->assertEquals("'hello'", $cmd->escape('hello'));
		$this->assertEquals("'hello t\\' you'", $cmd->escape('hello t\' you'));
		
		$minion->cache->set('system.kernel', 'windows_nt', 0);
		$cmd->_ec = null;
		
		$this->assertEquals('"hello"', $cmd->escape('hello'));
		$this->assertEquals('"hello t\\" you"', $cmd->escape('hello t" you'));
	}
}