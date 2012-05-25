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

	function run($target, &$local_cache=null) {

		$this->minion->log("{$this->indent}+ {$target}");
		
		if(isset($local_cache[$target])) {
			$this->minion->log("{$this->indent}- {$target} (cached)");
			return $local_cache[$target];
		}

		$deps = (isset($this->states[$target])) ? $this->states[$target] : array();
		$run_required = false;

		$this->indent = str_repeat('  ', ++$this->level);
		foreach($deps as $dep) {
			$run_required |= $this->run($dep, $local_cache);
		}
		$this->indent = str_repeat('  ', --$this->level);

		//$this->log("? " . var_export($run_required, true));

		// run this target, forcing if any dependants were updated
		$result = $this->minion->speck($target, $run_required);

		if($this->minion->cache->get_expiry($target) == Minion_CACHE::CACHE_FLAG) {
			//$this->log("FLAG: " . var_export($result, true));
			$run_required = (bool) $result;
		}
		$action = $run_required ? "REBUILT" : "up to date";
		$this->minion->log("{$this->indent}- {$target} [{$action}]");
		
		$local_cache[$target] = $run_required;
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