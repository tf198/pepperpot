PepperPot
---------

Pepperpot is a remote execution and configuration management library written in PHP
based on the excellent python library SaltStack_.  Much of the task and
state logic has been ported almost as-is but the underlying transport is completely different.

.. _SaltStack: http://saltstack.org

Warning
=======
This is currently under development and will probably not work as expected - please check back later!

Requirements
============

In contrast to SaltStacks requirements, PepperPot minions only require a ssh server to be installed on
the target - all other requirements will be installed as needed.

At a bare minimum a valid user and password is required - this should allow basic reporting from the
system.  For administration an appropriate sudoers entry is required with the NOPASSWD option set - there
is currently no support for sending the password with the sudo commands.  Public key authentication is also
supported if required.

So in short, a fresh system with openssh-server installed and a user account - nothing more!

Pepperpot can use one of two underlying SSH libraries:
1) libssh2_ (recommended) - the official PHP module with packages available for most systems
e.g. `apt-get install libssh2-php

2) PHPSecLib_ - PHP only implementation, slow but may be your only option on windows.  Components need
to be on the current PHP include path.

.. _libssh2: http://www.php.net/manual/en/book.ssh2.php
.. _PHPSecLib: http://phpseclib.sourceforge.net

Command line usage
==================

Create a `machines.php` file
	<?php
	return array(
	  'localhost' => array('local' => true),
	  'testing' => array('ip' => '10.0.0.1', 'username' => 'bob', 'password' => 'secretpass'),
	);
	?>
	
Invoke a predefined task on all targets
	> php run.php % task.system.os
	
Invoke a task on a set of targets
	> php run.php test% task.system.hostname

API usage
=========

	<?php
	$info = array('ip' => '10.0.0.1', 'port' => 22, 'username' => 'bob', 'password' => 'secretpass');
	$minion = new Minion("My Minion", $info);
	
	echo $minion->speck('system.hostname');
	?>