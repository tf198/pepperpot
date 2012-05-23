<?php
require_once('cmd_test.php');

class MinionTest extends PHPUnit_Framework_TestCase {
	
	function testParseURI() {
		$this->assertEquals(array('system', 'os', array('one', 'two', 'thre:e')), Minion::parse_uri('system.os:one:two:thre\\:e'));
	}
}