<?

require_once "cmd_test.php";

class System_Test extends Cmd_Test {

  function testKernel() {
    $this->assertEquals('linux', $this->minion->speck('system.kernel'));
  }

  function testOS() {
    $this->assertEquals('ubuntu', $this->minion->speck('system.os'));
  }

}

?>
