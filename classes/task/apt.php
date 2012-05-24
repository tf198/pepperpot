<?php

class Task_Apt extends Task_Pkg {

  function reload() {
    $this->cmd->run('apt-get update', true);
  }

  function install($name) {
    $this->cmd->run('apt-get -y install ' . $this->cmd->escape($name), true);
  }

  function available($name) {
    $output = $this->cmd->run_stdout('apt-cache show ' . $this->cmd->escape($name));
    foreach ($output as $line) {
      if (substr($line, 0, 8) == 'Version:') {
        return trim(substr($line, 9));
      }
    }
    throw new Task_Exception("Failed to get availble version for '{$name}'");
  }

  function current($name) {
    try {
      $result = $this->dpkg($name);
      return $result[$name];
    } catch (Task_Exception $e) {
      return null;
    }
  }

  function packages() {
    return $this->dpkg();
  }

  function dpkg($query=null) {
    $cmd = "dpkg -l";
    if ($query)
      $cmd .= " " . $this->cmd->escape($query);
    $data = $this->cmd->run_stdout($cmd);
    $markers = explode('-', $data[4]);
    for ($i = 0; $i < 4; $i++)
      $markers[$i] = strlen($markers[$i]) + 1;

    $result = array();
    for ($i = 5, $c = count($data); $i < $c; $i++) {
      $key = trim(substr($data[$i], $markers[0], $markers[1]));
      $value = trim(substr($data[$i], $markers[0] + $markers[1], $markers[2]));
      $result[$key] = $value;
    }

    return $result;
  }

}
