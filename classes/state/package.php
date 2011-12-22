<?php

class State_Package extends State_Base {
  
  function installed($name) {
    return $this->version($name, "");
  }
  
  function latest($name) {
    $latest = $this->grunt->task('pkg')->available($name);
    return $this->version($name, $latest);
  }
  
  function version($name, $min) {
    $current = $this->grunt->task('pkg')->current($name);
    
    if($current!=null && $current >= $min) return true;
    $this->grunt->task('pkg')->install($name);
    $new = $this->grunt->task('pkg')->current($name);
    return ($new >= $min);
  }
}