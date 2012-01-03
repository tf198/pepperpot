<?php

class State_Base {
  protected $minion;
  
  function __construct($minion) {
    $this->minion = $minion;
  }
}