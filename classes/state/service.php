<?php

/**
 * General purpose service state wrapper
 */
class State_Service extends State_Base {
  function running($name) {
    $current = $this->grunt->task('probe')->get('ps', $name);
    return true;
  }
}