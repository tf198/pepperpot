<?php

class Task_Pkg extends Task_Base {
  static function handler($grunt) {
  	$os = $grunt->task('system')->grain('os');
    if(!$os) throw new Task_Exception("No os information available");
    switch($os) {
      case 'ubuntu':
      case 'debian':
        return new Task_Apt($grunt);
      default:
        throw new Task_Exception("No Pkg implementation available for {$os}");
    }
  }
}
