<?
$ts = microtime(true);

if($argc<4) _fail("Not enough arguments");

$identifier = $argv[1];
$task = $argv[2];
$method = $argv[3];
$params = array_slice($argv, 4);

$config = include("machines.php");

require_once "classes/pepperpot.php";
PepperPot::register();

$i = 0;
foreach($config as $name => $info) {
  if(preg_match("/{$identifier}/", $name)) {
    try {
      $machine = new Minion($info);
      $result = call_user_func_array(array($machine->task($task), $method), $params);
      printf("%-20s: %s\n", $name, print_r($result, true));
      $i++;
    } catch(Exception $e) {
      fputs(STDERR, "{$name}: {$e->getMessage()}\n");
    }
  }
}

printf("Contacted %d hosts in %dms using %dKb\n", $i, (microtime(true)-$ts)*1000, memory_get_peak_usage()/1024);

function _fail($message, $code=1) {
  global $argv;
  fputs(STDERR, $message . "\n");
  fputs(STDERR, "usage: php {$argv[0]} <identifier> <task> <method>\n");
  exit($code);
}
?>
