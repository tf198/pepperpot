<?php
class PepperState {

	private $states;
	private $minion;

	private $level=0;
	private $indent='';

	function __construct($minion, $states) {
		$this->states = $states;
		$this->minion = $minion;
	}

	function run($target, &$run_cache=null) {

		$this->minion->log("{$this->indent}+ {$target}");
		
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
		
		$run_required = false;

		$this->indent = str_repeat('  ', ++$this->level);
		foreach($deps as $dep) {
			$run_required |= $this->run($dep, $run_cache);
		}
		$this->indent = str_repeat('  ', --$this->level);

		// run this target, forcing if any dependants were updated
		$result = $this->minion->speck($target, $run_required);

		$t = $this->minion->task($task);
		if($t->cache_time($method) == Minion_Cache::CACHE_FLAG) {
			$run_required = (bool) $result;
		}
		
		
		$action = $run_required ? "REBUILT" : "up to date";
		$this->minion->log("{$this->indent}- {$target} [{$action}]");
		
		$run_cache[$target] = $run_required;
		return $run_required;
	}
	
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