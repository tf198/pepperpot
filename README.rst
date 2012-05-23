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

* libssh2_ (recommended) - the official PHP module with packages available for most systems e.g. ``apt-get install libssh2-php``

* PHPSecLib_ - PHP only implementation, slow but may be your only option on windows.  Components need to be on the current PHP include path.

.. _libssh2: http://www.php.net/manual/en/book.ssh2.php
.. _PHPSecLib: http://phpseclib.sourceforge.net

Command line usage
==================

Create a ``machines.php`` file::

	<?php
	return array(
	  'localhost' => array('local' => true),
	  'testing' => array('ip' => '10.0.0.1', 'username' => 'bob', 'password' => 'secretpass'),
	);
	?>
	
Invoke a predefined task on all targets::

	> php run.php % system.os
	
Invoke a task on a set of targets::

	> php run.php test% system.hostname
   
If there is a folder called ``cache`` then ``run.php`` will persist cached values for the machines to this folder. 

API usage
=========
::

	<?php
   
   // a basic autoloader - use your own if required
   require_once "classes/pepperpot.php";
   PepperPot::register();
   
   // Minions are configure via an array of basic info
	$info = array('ip' => '10.0.0.1', 'port' => 22, 'username' => 'bob', 'password' => 'secretpass');
	$minion = new Minion("My Minion", $info);
	
   // speck calls a component and caches the result as appropriate
	echo $minion->speck('system.hostname');
   
   /**
   * optionally you can store the cache for a future run and pass it as the third argument to the constructor
   * $cache = unserialize(file_get_contents('cache.dat'));
   * $minion = new Minion($name, $info, $cache);
   */
   file_put_contents('cache.dat', serialize($minion->cache));
	?>
   
Caching
=======

During a run all values are cached for a time set by the class containing the component depending on the type of information
returned e.g. ``system.hostname`` and ``system.os`` are cached forever but ``system.uptime`` is always re-queried.  
You can manually expire a cached value by calling ``$minion->cache->delete('system.hostname')`` in the event that you have modified something
on the system.  As in the above example, the cache can be persisted between sessions which drastically reduces the number of commands
that need to be executed.

Tasks and states should take full advantage of the caching system for pre-requisites.

Tasks
=====

Tasks can do one of three things:

1) Return a small piece of information about the system.  The method implementation should include a cache time settings and users should
try to call them using the ``speck()`` interface to take advantage of the caching. Examples are ``system.os`` and ``network.mac:eth0``

2) Perform a specific action.  This should be kept as small as possible, with the majority mapping to a single system call on the remote machine
e.g. ``$minion->task('file')->chmod('/etc/motd', 0644)`` or ``$minion->task('service')->start('apache2')``

3) Bring the system to a specific state.  These are more compicated methods that check existing conditions and act accordingly.  By convention they
should be prefixed with ``ensure_`` e.g. ``service.ensure_running:apache2``.  They can make decisions based on cached values by using ``speck()`` or
forcing a remote call.
