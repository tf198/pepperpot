<?php
/**
 * PepperPot makefile processor
 * Tries to bring a system to a specified state processing dependancies as required
 * @author Tris Forster
 * @package PepperPot
 */
class PepperState {

	/**
	 * Internal dependancy tree representation
	 * @var multitype:string
	 */
	private $states;
	
	/**
	 * Reference to current minion
	 * @var Minion
	 */
	private $minion;

	/**
	 * Track current recusion level
	 * @var int
	 */
	private $level=0;
	
	/**
	 * Pretty print stack
	 * @var string
	 */
	private $indent='';

	/**
	 * Construct a new PepperState object
	 * @param Minion $minion
	 * @param multitype:string $states
	 */
	function __construct($minion, $states) {
		$this->states = $states;
		$this->minion = $minion;
	}

	/**
	 * Run a specific target
	 * @param string $target			target to run
	 * @param multitype:bool $run_cache	internal use only
	 */
	function run($target, &$run_cache=null) {

		$this->minion->log("{$this->indent}+ {$target}");
		
		// return from run cache if possible - allows for complex dependancy trees
		if(isset($run_cache[$target])) {
			$this->minion->log("{$this->indent}- {$target} (cached)");
			return $run_cache[$target];
		}

		list($task, $method, $params) = Minion::parse_uri($target);
		
		// explicit deps
		$deps = (isset($this->states[$target])) ? $this->states[$target] : array();
		
		// wildcard deps
		$key = "{$task}.{$method}:%";
		if(isset($this->states[$key])) {
			$tr = array();
			for($i=0,$c=count($params); $i<$c; $i++) $tr['%' . ($i+1)] = $params[$i];
			foreach($this->states[$key] as $dep) $deps[] = strtr($dep, $tr);
		}
		
		// base position depends on whether there is a current persistent result for this target
		$run_required = !$this->minion->cache->contains($target);

		$this->indent = str_repeat('  ', ++$this->level);
		foreach($deps as $dep) {
			$run_required |= $this->run($dep, $run_cache);
		}
		$this->indent = str_repeat('  ', --$this->level);

		// run the target if neccesary
		if($run_required) {
			$result = $this->minion->invoke($target, true);

			// allow FLAG methods to cancel the bubble
			$t = $this->minion->task($task);
			if($t->cache_time($method) == Minion_Cache::CACHE_FLAG) {
				$run_required = (bool) $result;
			}
		}
		
		// report the outcome
		$action = $run_required ? "REBUILT" : "up to date";
		$this->minion->log("{$this->indent}- {$target} [{$action}]");
		
		$run_cache[$target] = $run_required;
		return $run_required;
	}
	
	/**
	 * Parser for psudo-yaml makefiles
	 * @param string $data	raw data
	 * @throws Exception
	 */
	static function parse($data) {
		$lines = explode("\n", str_replace("\r", "", $data));
		$result = array();
		$subset = null;
		foreach($lines as $line) {
			// remove comments
			if(($p = strpos($line, '#')) !== false) {
				$line = rtrim(substr($line, 0, $p));
			}
			
			// remove blank lines
			if($line == '') continue;
			
			if($line{0} == ' ' || $line{0} == "\t") {
				if($subset === null) throw new Exception("No section found");
				$subset[] = trim($line);
			} else {
				$key = trim($line);
				$result[$key] = array();
				$subset = &$result[$key];
			}
		}
		return $result;
	}
}