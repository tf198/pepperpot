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

  function testReload() {
    $this->cmd->data['sudo -n apt-get update'] = array();
    $this->minion->task('apt')->reload();
  }

  function testAvailable() {
    $this->cmd->data['apt-cache show "mypackage"'] = array(
        'Name: MyPackage', 'Version: 1.0.2', 'Description: My dummy package'
    );

    $this->assertEquals($this->minion->task('apt')->available('mypackage'), '1.0.2');
  }

  function testCurrent() {
    $this->cmd->data['dpkg -l "package1"'] = $this->dpkg;

    $this->assertEquals($this->minion->task('apt')->current('package1'), '1.0.1');
  }

  function testInstall() {
    $this->cmd->data['sudo -n apt-get -y install "mypackage"'] = array();
    $this->minion->task('apt')->install('mypackage');
  }

  function testUpToDate() {
    $this->cmd->data['dpkg -l "package1"'] = $this->dpkg;
    $this->cmd->data['dpkg -l "package2"'] = $this->dpkg;
    $this->cmd->data['apt-cache show "package1"'] = array('Version: 1.0.1');
    $this->cmd->data['apt-cache show "package2"'] = array('Version: 1.0.1');

    $this->assertTrue($this->minion->task('apt')->up_to_date('package1'), true);
    $this->assertFalse($this->minion->task('apt')->up_to_date('package2'), false);
  }

  function testPackages() {
    $this->cmd->data['dpkg -l'] = $this->dpkg;

    $this->assertEquals($this->minion->task('apt')->packages(), array('package1' => '1.0.1', 'package2' => '1.0.2'));
  }

}

?>
