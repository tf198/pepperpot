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
}