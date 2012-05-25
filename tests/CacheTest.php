<?php
class CacheTest extends PHPUnit_Framework_TestCase {
  
  function setUp() {
    $this->cache = new Minion_Cache();
    $this->cache->set('never', 'one', Minion_Cache::CACHE_NEVER);
    $this->cache->set('private', 'secret', Minion_Cache::CACHE_PRIVATE);
    $this->cache->set('session', 3, Minion_Cache::CACHE_SESSION);
    $this->cache->set('short', 'four', time()+1);
    $this->cache->set('hour', 5, time() + Minion_Cache::CACHE_HOUR);
    $this->cache->set('infinite', 'six', Minion_Cache::CACHE_INFINITE);
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
    $this->assertEquals(array('hour', 'infinite'), array_keys($this->cache->data()));
  }
  
  function testSleep() {
    $data = serialize($this->cache);
    $this->cache = unserialize($data);
    $this->assertEquals(array('private', 'short', 'hour', 'infinite'), $this->cache->keys());
  }
  
  function testTimestamp() {
  	$now = time();
  	$this->cache->set('test1', 'value', $now);
  	$this->assertEquals($now, $this->cache->get_expiry('test1'));
  }
  
}

?>
