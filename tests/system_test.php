<?

require_once "cmd_test.php";

class System_Test extends Cmd_Test {

  function testKernel() {
    $this->assertEquals('linux', $this->minion->speck('system.kernel'));
  }

  function testOS() {
    $this->assertEquals('ubuntu', $this->minion->speck('system.os'));
  }
  
  function testKernelVersion() {
    $this->assertEquals('3.0.0', $this->minion->speck('system.kernel_version'));
  }
  
  function testCPUInfo() {
    $data =<<< EOF
vendor_id       : GenuineIntel
cpu cores       : 2

EOF;
    $this->cmd->data['cat /proc/cpuinfo'] = explode("\n", $data);
    $this->assertEquals(
            array(
                'vendor_id' => 'GenuineIntel',
                'cpu cores' => '2',
            ), $this->minion->speck('system.cpuinfo'));
  }
  
  function testHostname() {
    $this->cmd->data['hostname'] = array('test_system');
    $this->assertEquals('test_system', $this->minion->speck('system.hostname'));
  }
  
  function testTimeOffset() {
    $this->cmd->data['date -R'] = array(date('r'));
    $this->assertEquals(0, $this->minion->speck('system.time_offset'));
  }

}

?>
