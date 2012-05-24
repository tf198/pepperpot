<?

require_once('classes/pepperpot.php');
PepperPot::register();

abstract class Cmd_Test extends PHPUnit_Framework_TestCase {

	function setUp() {
		$this->minion = new Minion('test', array());
		$this->cmd = new Mock_Cmd($this->minion);
		$this->minion->_tasks['cmd'] = $this->cmd;
	}
	
	function assertEndOfStream() {
		$this->assertTrue($this->cmd->endofstream());
	}
}

class Mock_Cmd extends Task_Cmd {
	
	private $data = array();
	
	const SHELL_PROMPT = '$ ';
	
	static $machines = array(
			'blank' => array(),
			'ubuntu' => array(
					'system.os' => array('ubuntu', -1),
					'system.kernel' => array('linux', -1),
					),
			);
	
	function exec($cmd, &$output, &$ret) {
		// verify that the correct command has been passed
		$expected = array_shift($this->data);
		
		if($expected != self::SHELL_PROMPT . $cmd) {
			var_dump($this->data);
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
		} else {
			$ret = 0;
		}
		$output = array_splice($this->data, 0, $i);
		if($ret) array_pop($output); // remove return code line
	}
	
	public function endofstream() {
		return count($this->data) == 0;
	}
	
	public function __set($name, $value) {
		if($name == 'session') {
			if($this->data) throw new Exception("Previous run didn't finish");
			$value = str_replace("\r", "", $value);
			$this->data = explode("\n", $value);
		}
	}
	
	public function load($type) {
		$this->minion->cache->_cache = self::$machines[$type];
	}
}
?>
