<?php

class Task_Pkg extends Task_Base {
  static function handler($minion) {
  	switch($minion->os) {
      case 'ubuntu':
      case 'debian':
        return new Task_Apt($minion);
      default:
        throw new Task_Exception("No Pkg implementation available for {$minion->os}");
    }
  }
}
