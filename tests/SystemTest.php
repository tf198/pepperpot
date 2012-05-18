<?

require_once "cmd_test.php";

class System_Test extends Cmd_Test {

  function setUp() {
  	parent::setUp();
  	$this->cmd->load('ubuntu');
  }
	
  function testKernel() {
    $this->assertEquals('linux', $this->minion->speck('task.system.kernel'));
  }

  function testOS() {
    $this->assertEquals('ubuntu', $this->minion->speck('task.system.os'));
  }
  
  function testKernelVersion() {
  	$this->cmd->session = <<< EOF
$ uname -r
3.0.0
EOF;
  	
    $this->assertEquals('3.0.0', $this->minion->speck('task.system.kernel_version'));
  }
  
  function testCPUInfo() {
    $this->cmd->session = <<< EOF
$ cat /proc/cpuinfo
vendor_id       : GenuineIntel
cpu cores       : 2
EOF;
    
    $this->assertEquals(
            array(
                'vendor_id' => 'GenuineIntel',
                'cpu cores' => '2',
            ), $this->minion->speck('task.system.cpuinfo'));
  }
  
  function testHostname() {
    $this->cmd->session = <<< EOF
$ hostname
test_system
EOF;

    $this->assertEquals('test_system', $this->minion->speck('task.system.hostname'));
  }
  
  function testTimeOffset() {
  	$now = date('r');
  	$this->cmd->session = <<< EOF
$ date -R
{$now}
EOF;

    $this->assertEquals(0, $this->minion->speck('task.system.time_offset'));
  }

}

?>
