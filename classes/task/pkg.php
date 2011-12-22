<?php

class Task_Pkg extends Task_Base {
  function handler() {
    $os = $this->grunt->task('probe')->get('os', 'name');
    switch($os) {
      case 'ubuntu':
      case 'debian':
        return new Task_Apt($this->grunt);
      default:
        throw new Task_Exception("No Pkg implementation available for {$os}");
    }
  }
}