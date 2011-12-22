<?php

class Task_Probe extends Task_Base {
  private $_data = array(
      'os' => array('name' => 'ubuntu', 'version' => '11.10'),
      'package' => array('')
  );
  
  function get($probe, $key) {
    if(!isset($this->_data[$probe])) return null;
    if(!isset($this->_data[$probe][$key])) return null;
    return $this->_data[$probe][$key];
  }
  
  function refresh($probe, $key) {
    return $this->get($probe, $key);
  }
}