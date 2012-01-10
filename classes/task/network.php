<?php

class Task_Network extends Task_Base {
  public $cache_time = array('interfaces' => 30, 'dns' => 30);
  
  function interfaces() {
    switch($this->minion->os()) {
      case 'ubuntu':
        $data = $this->minion->task('cmd')->run_stdout("ip link");
        return $this->_parse_ip_addr($data);
      default:
        throw new Task_Exception("Not implemented");
    }
  }
  
  function ips() {
    switch($this->minion->speck('system.os')) {
      case 'ubuntu':
        $data = $this->minion->task('cmd')->run_stdout("ip addr");
        return $this->_parse_ip_addr($data);
      default:
        throw new Task_Exception("Not implemented");
    }
  }
  
  function iface($name) {
    $interfaces = $this->minion->speck('network.interfaces');
    if(!isset($interfaces[$name])) throw new Task_Exception("No such interface: {$name}");
    return $interfaces[$name];
  }
  
  function mac($name) {
    $iface = $this->iface($name);
    return $iface['mac'];
  }
  
  function dns_servers() {
    switch($this->minion->os()) {
      case 'ubuntu':
        $data = $this->minion->task('cmd')->run_stdout('cat /etc/resolv.conf');
        $result = array();
        foreach($data as $line) {
          $parts = explode(' ', $line, 2);
          if($parts[0]=='nameserver') $result[] = $parts[1];
        }
        return $result;
      default:
        throw new Task_Exception("No implemented");
    }
  }
  
  /**
   * Parser for output from 'ip addr' and ip link'
   * @param array $data lines
   * @return array network hardware info
   */
  function _parse_ip_addr($data) {
    $result = array();
    $c = count($data);
    $iface = null;
    $i = 0;
    while($i<$c) {
      if(substr($data[$i], 0, 1)!=' ') { // new section
        if($iface) $result[$iface['name']] = $iface;
        $first = explode(' ', $data[$i++]);
        $second = explode(' ', $data[$i]);
        $iface = array(
            'name' => substr($first[1], 0, -1),
            'type' => $second[4],
            'mac' => $second[5],
            'brd' => $second[7],
        );
        $iface['options'] = explode(',', substr($first[2], 1, -1));
        for($j=4, $d=count($first); $j<$d; $j+=2) {
          $iface[$first[$j-1]] = $first[$j];
        }
      } else { // normal line
        $parts = explode(' ', $data[$i]);
        if($parts[4]!="") {
          $addr = explode('/', $parts[5]);
          
          $inet = array(
              'addr' => $addr[0],
              'mask' => $addr[1],
          );
          for($j=7, $d=count($parts); $j<$d; $j+=2) {
            $inet[$parts[$j-1]] = $parts[$j];
          }
          $iface[$parts[4]] = $inet;
        }
      }
      $i++;
    }
    if($iface) $result[$iface['name']] = $iface;
    return $result;
  }
}