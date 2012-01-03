<?
require_once "cmd_test.php";

class System_Test extends Cmd_Test {
	function testOS() {
		$this->assertEquals($this->minion->speck('system.os'), 'ubuntu');	
	}
	
	function testKernel() {
		$this->assertEquals($this->minion->speck('system.kernel'), '3.0.0');
	}
}
?>
