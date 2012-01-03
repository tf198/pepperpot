<?php

class State_Package extends State_Base {
  
  function installed($name) {
    return $this->version($name, "");
  }
  
  function latest($name) {
    $latest = $this->minion->task('pkg')->available($name);
    return $this->version($name, $latest);
  }
  
  function version($name, $min) {
    $current = $this->minion->task('pkg')->current($name);
    
    if($current!=null && $current >= $min) return true;
    $this->minion->task('pkg')->install($name);
    $new = $this->minion->task('pkg')->current($name);
    return ($new >= $min);
  }
}