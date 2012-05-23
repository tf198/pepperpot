<?php
require_once('cmd_test.php');

class MinionTest extends PHPUnit_Framework_TestCase {
	
	function testParseURI() {
		$minion = new Minion('test', array());
		$result = $minion->_parse_uri('system.os:one:two:thre\\:e');
		$this->assertEquals(array('one', 'two', 'thre:e'),$result[2]);
	}
}