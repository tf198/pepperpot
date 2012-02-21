<?

require_once('classes/pepperpot.php');
PepperPot::register();

abstract class Cmd_Test extends PHPUnit_Framework_TestCase {

  function setUp() {
    $this->minion = new Minion('test', array());
    $this->cmd = new Mock_Cmd($this->minion);
    $this->minion->_components['task']['cmd'] = $this->cmd;
  }
}

class Mock_Cmd extends Task_Cmd {

  // pretend to be an ubuntu system
  public $data = array(
      'uname -r' => array('3.0.0'),
      'uname -s' => array('Linux'),
      'echo %OS%' => array('%OS%'),
      'test -f /etc/issue.net' => array(),
      'cat /etc/issue.net' => array('Ubuntu 0.0'),
  );

  function _exec($cmd, &$output, &$ret) {
    if (isset($this->data[$cmd])) {
      $output = $this->data[$cmd];
      $ret = 0;
    } else {
      $output = array('No mock command defined');
      $ret = -1;
    }
  }

}

?>
