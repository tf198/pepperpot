<?php
class StateTest extends PHPUnit_Framework_TestCase {
	
	function setUp() {
		$this->minion = new Minion(array('local' => true));
		$this->task = $this->minion->task('statetest');
	}
	
	function testBasic() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_noop:1', 'statetest.test_noop:2'),
		);
		$stack = new PepperState($this->minion, $states);
		// first time should call everything
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('noop-1', 'noop-2', 'target-2', 'target-1'), $this->task->stack());
		
		// second time should call nothing
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array(), $this->task->stack());
	}
	
	function testFalseFlags() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_flag:1:false', 'statetest.test_flag:2:false'),
		);
		//Minion::$logger = new Logger;		
		$stack = new PepperState($this->minion, $states);
		
		// first time should call everything
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	
		// second time should just call the flags
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2'), $this->task->stack());
		
		// should be the same
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2'), $this->task->stack());
	}
	
	function testOneTrueFlags() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_flag:1:true', 'statetest.test_flag:2:false'),
		);
		//Minion::$logger = new Logger;
		$stack = new PepperState($this->minion, $states);
		
		// first time should call everything
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	
		// Flag at the bottom of the stack causes everything to be rebuilt
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	
		// should be the same
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	}
	
	function testTwoTrueFlags() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_flag:1:true', 'statetest.test_flag:2:true'),
		);
		//Minion::$logger = new Logger;
		$stack = new PepperState($this->minion, $states);
		
		// first time should call everything
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	
		// Flag at the bottom of the stack causes everything to be rebuilt
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	
		// should be the same
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('flag-1', 'flag-2', 'target-2', 'target-1'), $this->task->stack());
	}
	
	function testDynamicFlag() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_dynamic:1'),
		);
		//Minion::$logger = new Logger;
		$stack = new PepperState($this->minion, $states);
		
		// first time should call everything
		$GLOBALS['dynamic_1'] = false;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'target-2', 'target-1'), $this->task->stack());
		
		// Nothing changed
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1'), $this->task->stack());
		
		// Toggle the flag
		$GLOBALS['dynamic_1'] = true;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'target-2', 'target-1'), $this->task->stack());
	}
	
	function testSimpleCascade() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2', 'statetest.test_dynamic:1'),
			'statetest.test_target:2' => array('statetest.test_dynamic:2'),
		);
		//Minion::$logger = new Logger;
		$stack = new PepperState($this->minion, $states);
		
		// first time should call everything
		$GLOBALS['dynamic_1'] = false;
		$GLOBALS['dynamic_2'] = false;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-2', 'target-2', 'dynamic-1', 'target-1'), $this->task->stack());
		
		// only target_1 should get called
		$GLOBALS['dynamic_1'] = true;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-2', 'dynamic-1', 'target-1'), $this->task->stack());
		
		$GLOBALS['dynamic_1'] = false;
		$GLOBALS['dynamic_2'] = true;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-2', 'target-2', 'dynamic-1', 'target-1'), $this->task->stack());
	}
	
	function testComplexCascade() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_target:2', 'statetest.test_target:3'),
			'statetest.test_target:2' => array('statetest.test_dynamic:1', 'statetest.test_target:3'),
			'statetest.test_target:3' => array('statetest.test_dynamic:2'),
		);
		//Minion::$logger = new Logger;
		$stack = new PepperState($this->minion, $states);
		
		// first time should call everything
		$GLOBALS['dynamic_1'] = false;
		$GLOBALS['dynamic_2'] = false;
		$cache = array();
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'dynamic-2', 'target-3', 'target-2', 'target-1'), $this->task->stack());
		
		// just run the flag targets
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'dynamic-2'), $this->task->stack());
		
		$GLOBALS['dynamic_1'] = true;
		// target-3 wont be run
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'dynamic-2', 'target-2', 'target-1'), $this->task->stack());
		
		$GLOBALS['dynamic_1'] = false;
		$GLOBALS['dynamic_2'] = true;
		// dynamic_2 affects everything
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'dynamic-2', 'target-3', 'target-2', 'target-1'), $this->task->stack());
	}
	
	function testCached() {
		$states = array(
			'statetest.test_target:1' => array('statetest.test_cached:1', 'statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_dynamic:1'),
		);
		//Minion::$logger = new Logger;
		$stack = new PepperState($this->minion, $states);
		
		$GLOBALS['dynamic_1'] = false;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('cached-1', 'dynamic-1', 'target-2', 'target-1'), $this->task->stack());
		
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1'), $this->task->stack());
		
		$GLOBALS['dynamic_1'] = true;
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'target-2', 'target-1'), $this->task->stack());
		
		// CACHE_INFINITE should survive clean() and not run again
		$globals['dynamic_1'] = false;
		$this->minion->cache->clean();
		$stack->run('statetest.test_target:1');
		$this->assertEquals(array('dynamic-1', 'target-2', 'target-1'), $this->task->stack());
	}
	
	function testParse() {
		$data = <<< EOF
# First target
statetest.test_target:1
	statetest.test_cached:1
	statetest.test_target:2 # inline comment

# Second target
statetest.test_target:2
  # indented comment
  statetest.test_dynamic:1
EOF;
		$states = array(
			'statetest.test_target:1' => array('statetest.test_cached:1', 'statetest.test_target:2'),
			'statetest.test_target:2' => array('statetest.test_dynamic:1'),
		);
		
		$this->assertEquals($states, PepperState::parse($data));
	}
}

class Task_StateTest extends Task_Base {
	public $cache_time = array(
		'test_flag' => Minion_Cache::CACHE_FLAG,
		'test_dynamic' => Minion_Cache::CACHE_FLAG,
		'test_cached' => Minion_Cache::CACHE_INFINITE,
	);
	
	public $_stack = array();
	
	function stack() {
		$stack = $this->_stack;
		$this->_stack = array();
		return $stack;
	}
	
	function test_noop($id) {
		$this->_stack[] = "noop-{$id}";
	}
	
	function test_flag($id, $flag) {
		$this->_stack[] = "flag-{$id}";
		return $flag == 'true';
	}
	
	function test_dynamic($id) {
		$this->_stack[] = "dynamic-{$id}";
		return $GLOBALS['dynamic_' . $id];
	}
	
	function test_target($id) {
		$this->_stack[] = "target-{$id}";
	}
	
	function test_cached($id) {
		$this->_stack[] = "cached-{$id}";
	}
}

class Logger {
	function log($message, $level=LEVEL_INFO) { fputs(STDOUT, $message . "\n"); }
}