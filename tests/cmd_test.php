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
	
	private $data = array();
	
	const SHELL_PROMPT = '$ ';
	
	static $machines = array(
			'blank' => array(),
			'ubuntu' => array(
					'task.system.os' => array('ubuntu', -1),
					'task.system.kernel' => array('linux', -1),
					),
			);
	
	function exec($cmd, &$output, &$ret) {
		// verify that the correct command has been passed
		$expected = array_shift($this->data);
		
		if($expected != self::SHELL_PROMPT . $cmd) {
			$output = array('Expected ' . $expected);
			$ret = -1;
			return;
		}
		// get the output
		$l = strlen(self::SHELL_PROMPT);
		for($i=0, $c=count($this->data); $i<$c; $i++) {
			if(substr($this->data[$i], 0, $l) == self::SHELL_PROMPT) {
				break;
			}
		}
		if($i && substr($this->data[$i-1], 0, 3) == '$?=') {
			$ret = (int) substr($this->data[$i-1], 3);
			$i--;
		} else {
			$ret = 0;
		}
		$output = array_splice($this->data, 0, $i);
	}
	
	public function endofstream() {
		return count($this->data) == 0;
	}
	
	public function __set($name, $value) {
		if($name == 'session') {
			$value = str_replace("\r", "", $value);
			$this->data = explode("\n", $value);
		}
	}
	
	public function load($type) {
		$this->minion->cache->_cache = self::$machines[$type];
	}
}
?>
