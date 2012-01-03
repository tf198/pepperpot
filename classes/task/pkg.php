<?php

class Task_Pkg extends Task_Base {
  static function handler($grunt) {
  	switch($grunt->os) {
      case 'ubuntu':
      case 'debian':
        return new Task_Apt($grunt);
      default:
        throw new Task_Exception("No Pkg implementation available for {$grunt->os}");
    }
  }
}
