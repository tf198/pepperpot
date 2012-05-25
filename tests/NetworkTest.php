<?php 
class TaskNetworkTest extends MockCmd_TestCase {
	function testInterfaces() {
		$this->cmd->load('ubuntu');
		$this->cmd->session = <<< EOF
$ ip addr
1: lo: <LOOPBACK,UP,LOWER_UP> mtu 16436 qdisc noqueue state UNKNOWN
    link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00
    inet 127.0.0.1/8 scope host lo
    inet6 ::1/128 scope host
       valid_lft forever preferred_lft forever
2: eth1: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc pfifo_fast state UP qlen 1000
    link/ether 01:02:03:04:05:06 brd ff:ff:ff:ff:ff:ff
    inet 10.0.0.1/24 brd 10.0.0.255 scope global eth1
    inet6 01::03:04:05:06/64 scope link
       valid_lft forever preferred_lft forever
EOF;
		
		$network = $this->minion->task('network');
		
		// non existant interface
		try {
			$network->iface('eth0');
			$this->fail();
		} catch(Task_Exception $e) {
			$this->assertEquals("No such interface: eth0", $e->getMessage());
		}
		
		$iface = $network->iface('eth1');
		$this->assertEquals('eth1', $iface['name']);
		$this->assertEquals('pfifo_fast', $iface['qdisc']);
		
		$this->assertEquals("01:02:03:04:05:06", $network->mac('eth1'));
	}
	
	function testDNSServers() {
		$this->cmd->session = <<< EOF
$ cat /etc/resolv.conf
nameserver 10.0.0.1
EOF;

		$this->cmd->load('ubuntu');
		$this->assertEquals(array('10.0.0.1'), $this->minion->task('network')->dns_servers());
	}
}
?>