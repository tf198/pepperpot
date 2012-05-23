<?php

require_once('classes/pepperpot.php');
PepperPot::register();
class_exists('Minion');

class Cache_Test extends PHPUnit_Framework_TestCase {
  
  private $data = array(
      array('never', 'one', Minion_Cache::CACHE_NEVER),
      array('private', 'secret', Minion_Cache::CACHE_PRIVATE),
      array('session', 3, Minion_Cache::CACHE_SESSION),
      array('short', 'four', 1),
      array('hour', 5, Minion_Cache::CACHE_HOUR),
      array('infinite', 'six', Minion_Cache::CACHE_INFINITE),
  );
  
  function setUp() {
    $this->cache = new Minion_Cache();
    foreach($this->data as $item) $this->cache->set($item[0], $item[1], $item[2]);
  }
  
  function testDefault() {
    $this->assertEquals($this->cache->keys(), array('private', 'session', 'short', 'hour', 'infinite'));
  }
  
  function testContains() {
    $this->assertTrue($this->cache->contains('private'));
    $this->assertTrue($this->cache->contains('session'));
    $this->assertTrue($this->cache->contains('short'));
    $this->assertTrue($this->cache->contains('hour'));
    $this->assertTrue($this->cache->contains('infinite'));
    // expire the short one
    $this->cache->_cache['short'][1] -= 20;
    $this->assertFalse($this->cache->contains('short'));
    // should still be in the cache though
    $this->assertEquals($this->cache->get('short'), 'four');
  }
  
  function testClean() {
    $this->cache->clean();
    $this->assertEquals($this->cache->keys(), array('private', 'short', 'hour', 'infinite'));
    
    // expire the short one
    $this->cache->_cache['short'][1] -= 20;
    $this->cache->clean();
    $this->assertEquals(array('private', 'hour', 'infinite'), $this->cache->keys());
    
    // check that private keys are removed for persisting
    $this->assertEquals(array('hour', 'infinite'), array_keys($this->cache->get_data()));
  }
  
  function testSleep() {
    $data = serialize($this->cache);
    $this->cache = unserialize($data);
    $this->assertEquals(array('private', 'short', 'hour', 'infinite'), $this->cache->keys());
  }
  
}

?>
