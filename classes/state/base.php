<?php

class State_Base {
  protected $minion;
  
  function __construct($minion) {
    $this->minion = $minion;
  }
  
  public static function handler($instance, $klass=null) {
  	return new $klass($instance);
  }
}