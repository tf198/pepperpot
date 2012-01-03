<?

require_once('classes/pepperpot.php');
PepperPot::register();

class Cmd_Test extends PHPUnit_Framework_TestCase {
	function setUp() {
		$this->minion = new Minion(array());
		$this->cmd = new Mock_Cmd($this->minion);
		$this->minion->_components['task']['cmd'] = $this->cmd;
	}
	
}

class Mock_Cmd extends Task_Cmd {

	public $data = array(
		'uname -r' => array('3.0.0'),
		'echo %OS%' => array('%OS%'),
		'test -f /etc/issue.net' => array(),
		'cat /etc/issue.net' => array('Ubuntu 0.0'),
	);

	function exec($cmd, &$output, &$ret) {
		//echo " >> {$cmd}\n";
		if(isset($this->data[$cmd])) {
			$output = $this->data[$cmd];
			$ret = 0;
		} else {
			$output = array('No mock command defined');
			$ret = -1;
		}
	}
}
?>
