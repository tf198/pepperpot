<?

require_once('cmd_test.php');

class Apt_Test extends Cmd_Test {

  private $dpkg = array(
      '', '', '',
      '    Name        Version     Description          ',
      '+++-===========-===========-=====================',
      'ii  package1    1.0.1       Dummy package 1      ',
      'ii  package2    1.0.2       Dummy package 2      ',
  );

  function setUp() {
  	parent::setUp();
  	$this->cmd->load('ubuntu');
  }
  
  function testReload() {
    $this->cmd->session = <<< EOF
$ sudo -n apt-get update
EOF;
    
    $this->minion->task('apt')->reload();
  	$this->assertTrue($this->cmd->endofstream());
  }

  function testAvailable() {
    $this->cmd->session = <<< EOF
$ apt-cache show "mypackage"
Name: MyPackage
Version: 1.0.2
Description: My Dummy package
EOF;

    $this->assertEquals($this->minion->task('apt')->available('mypackage'), '1.0.2');
  }

  function testCurrent() {
    $this->cmd->session = <<< EOF
$ dpkg -l "package1"
Desired  ...
| Status ...
|\ Err   ...
Name        Version     Description
+++-===========-===========-=====================
ii  package1    1.0.1       Dummy package 1
EOF;
    
    $this->assertEquals($this->minion->task('apt')->current('package1'), '1.0.1');
  }

  function testInstall() {
    $this->cmd->session = '$ sudo -n apt-get -y install "mypackage"';
    $this->minion->task('apt')->install('mypackage');
  }

  function testUpToDate() {
    $this->cmd->session = <<< EOF
$ dpkg -l "package1"
Desired  ...
| Status ...
|\ Err   ...
Name        Version     Description
+++-===========-===========-=====================
ii  package1    1.0.1       Dummy package 1
$ apt-cache show "package1"
...
Version: 1.0.1
...
$ dpkg -l "package2"
Desired  ...
| Status ...
|\ Err   ...
Name        Version     Description
+++-===========-===========-=====================
ii  package2    1.0.2       Dummy package 2
$ apt-cache show "package2"
...
Version: 1.0.1
...
EOF;

    $this->assertTrue($this->minion->task('apt')->up_to_date('package1'), true);
    $this->assertFalse($this->minion->task('apt')->up_to_date('package2'), false);
  }

  function testPackages() {
    $this->cmd->session = <<< EOF
$ dpkg -l
Desired  ...
| Status ...
|\ Err   ...
Name        Version     Description
+++-===========-===========-=====================
ii  package1    1.0.1       Dummy package 1
ii  package2    1.0.2       Dummy package 2
EOF;

    $this->assertEquals($this->minion->task('apt')->packages(), array('package1' => '1.0.1', 'package2' => '1.0.2'));
  }

}

?>
