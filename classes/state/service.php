<?php

/**
 * General purpose service state wrapper
 */
class State_Service extends State_Base {
  function running($name) {
  	$service = $this->minion->task('service');
  	
    if($service->status($name)) {
    	$this->minion->log("Service {$name} already running");
    } else {
    	$service->start($name);
    	$this->minion->log("Service {$name} started");
    }
  }
}
