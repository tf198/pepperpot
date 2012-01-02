<?php
class Task_System extends Task_Base {
  function os() {
    if($this->grunt->task('cmd')->run_stdout('echo %OS%') != "%OS%") return "windows";
    
    return "unknown";
  }
}
?>
