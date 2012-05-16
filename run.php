<?
define('MACHINES', 'machines.php');

$ts = microtime(true);

if($argc<3) _fail("Not enough arguments");

$identifier = $argv[1];
list($type, $klass, $method) = explode('.', $argv[2], 3);
$params = array_slice($argv, 3);

if(!file_exists(MACHINES)) _fail("Missing config file: " . MACHINES);

$config = include(MACHINES);

require_once "classes/pepperpot.php";
PepperPot::register();

$identifier = str_replace('%', '.*', $identifier);

$i = 0;
foreach($config as $name => $info) {
  if(preg_match("/^{$identifier}\$/", $name)) {
    try {
      // some very basic caching
      $cache_file = "cache/{$name}.dat";
      $cache = null;
      if(file_exists($cache_file)) {
      	fprintf(STDERR, "%20s: %s\n", $name, "CACHE> loaded from {$cache_file}");
        $cache = unserialize(file_get_contents($cache_file));
      }
      
      // create the minion
      $minion = new Minion($name, $info, $cache);
      
      // call the required function
      $obj = $minion->{$type}($klass);
      $r_c = new ReflectionClass($obj);
      $r_m = $r_c->getMethod($method);
      $result = $r_m->invokeArgs($obj, $params);
      
      $minion->log("RESULT> " . print_r($result, true));
      $i++;

      if(is_writable('cache')) {
      	$minion->log("CACHE> written to {$cache_file}");
      	file_put_contents($cache_file, serialize($minion->cache));
      }
      
    } catch(Exception $e) {
      //fputs(STDERR, "{$name}: {$e->getMessage()}\n");
      $minion->log("ERROR> " . $e->getMessage());
    }
  }
}

printf("Contacted %d hosts in %dms using %dKb\n", $i, (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);

function _fail($message, $code=1) {
  global $argv;
  fputs(STDERR, $message . "\n");
  fputs(STDERR, "usage: php {$argv[0]} <identifier> <type.task.method> [argument] ...\n");
  exit($code);
}
?>
