#!/usr/bin/env php
<?
define('MACHINES', 'machines.php');
define('PEPPERPOT_CACHE_DIR', 'cache');

$ts = microtime(true);

$parser = parse_args($argv);
if(count($parser['args']) < 2) _fail("Not enough arguments"); 

if(!file_exists(MACHINES)) _fail("Missing config file: " . MACHINES);
$config = include(MACHINES);

require_once "classes/pepperpot.php";
PepperPot::register();

// set up logging
Minion::$logger = new MinionLogger();

// set up the returner
if(isset($parser['opts']['r'])) {
	$klass = "Minion_Returner_" . ucfirst($parser['opts']['r']);
	if(!class_exists($klass)) _fail("No such returner: " . $klass);
	$returner = new $klass;
} else {
	$returner = new Minion_Returner_Console;
}

$use_cache = (file_exists(PEPPERPOT_CACHE_DIR) && !isset($parser['opts']['force']));

// allow wildcards in identifier
$identifier = str_replace('%', '.*', $parser['args'][0]);

$i = 0;
foreach($config as $name => $info) {
	if(!isset($info['id'])) $info['id'] = $name;

		if(preg_match("/^{$identifier}\$/", $name)) {
		try {
			// some very basic caching
			$cache_file = PEPPERPOT_CACHE_DIR . "/{$name}.json";
			$cache = null;
			if($use_cache && file_exists($cache_file)) {
				$data = json_decode(file_get_contents($cache_file), true);
				$cache = new Minion_Cache($data);
				fprintf(STDERR, "%20s: %s\n", $name, "CACHE> loaded from {$cache_file}");
			}

			// create the minion
			$minion = new Minion($info, $cache);
			$result = $minion->speck($parser['args'][1]);

			$returner->write($minion, $result);
			$i++;

			if($use_cache && is_writable('cache')) {
				$minion->cache->clean();
				file_put_contents($cache_file, json_encode($minion->cache->data()));
				$minion->log("CACHE> written to {$cache_file}");
			}

		} catch(Exception $e) {
			$minion->log("ERROR> " . $e->getMessage());
		}
	}
}

printf("Contacted %d hosts in %dms using %dKb\n", $i, (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);

function _fail($message, $code=1) {
	global $argv;
	fputs(STDERR, $message . "\n\n");
	fputs(STDERR, "usage: php {$argv[0]} [-r <returner>] [--force] <key>\n\n");
	fputs(STDERR, "    -r <returner>   Use a different returner (default: console)\n");
	fputs(STDERR, "    --force         Dont load cache");
	exit($code);
}

class MinionLogger {
	public function add($level, $message) {
		fputs(STDERR, $message . "\n");
	}
}

function parse_args($args) {
	$result = array('opts' => array(),'args' => array());
	for($i=1, $c=count($args); $i<$c; $i++) {
		$arg = $args[$i];
		if($arg{0} == '-') {
			if($arg{1} == '-') {
				$result['opts'][substr($arg, 2)] = true;
			} else {
				$result['opts'][substr($arg, 1)] = $args[++$i];
			}
		} else {
			$result['args'][] = $arg;
		}
	}
	return $result;
}
?>
