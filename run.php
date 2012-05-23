#!/usr/bin/env php
<?
define('MACHINES', 'machines.php');

$ts = microtime(true);

if($argc<3) _fail("Not enough arguments");

$identifier = $argv[1];

if(!file_exists(MACHINES)) _fail("Missing config file: " . MACHINES);

$config = include(MACHINES);

require_once "classes/pepperpot.php";
PepperPot::register();

// set up logging
Minion::$logger = new MinionLogger();

$returner = new Minion_Returner_Console();

// allow wildcards in identifier
$identifier = str_replace('%', '.*', $identifier);

$i = 0;
foreach($config as $name => $info) {
  if(preg_match("/^{$identifier}\$/", $name)) {
    try {
      // some very basic caching
      $cache_file = "cache/{$name}.json";
      $cache = null;
      if(file_exists($cache_file)) {
      	fprintf(STDERR, "%20s: %s\n", $name, "CACHE> loaded from {$cache_file}");
      	$cache = new Minion_Cache();
      	$cache->_cache = json_decode(file_get_contents($cache_file), true);
      }
      
      // create the minion
      $minion = new Minion($name, $info, $cache);
      $result = $minion->speck($argv[2]);
      
      $returner->write($minion, $result);
      $i++;

      if(is_writable('cache')) {
      	$minion->cache->clean();
      	file_put_contents($cache_file, json_encode($minion->cache->_cache));
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
  fputs(STDERR, $message . "\n");
  fputs(STDERR, "usage: php {$argv[0]} <identifier> <task.method> [argument] ...\n");
  exit($code);
}

class MinionLogger {
	public function add($level, $message) {
		fputs(STDERR, $message . "\n");
	}
}
?>
