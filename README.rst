PepperPot
---------

**WARNING** Abandonware

Pepperpot is a remote execution and configuration management library written in PHP
based on the excellent python library SaltStack_.  Much of the task and
state logic has been ported almost as-is but the underlying transport is completely different.

.. _SaltStack: http://saltstack.org

Status
======
This is currently under development but the core API is pretty fixed now.  At the moment the 
tasks are targeted at Ubuntu systems and are tested to varying degrees::

   Cmd               local interactions         (xxx  )
      SSH            libssh2                    (xx   )
      PHPSecLib      PHP only                   (xxx  )
   System            Hardware info              (xxx  )
   File              File operations            (xxx  )
   Pkg               Package manager (abstract) (xxx  )
      Apt            Apt package manager        (xxx  )
   Network           Network tasks              (xxx  )
   Service           Service                    (xxx  )
   User              User management            (x    )
   
   xxxxx production ready (not yet!)
   xxxx  full functionality and test set
   xxx   full functionality and partial test set
   xx    partial functionality and test set
   x     partial functionality only

Requirements
============

In contrast to SaltStacks requirements, PepperPot minions only require a ssh 
server to be installed on the target - all other requirements will be installed 
as needed.

At a bare minimum a valid user and password is required - this should allow basic 
reporting from the system.  For administration an appropriate sudoers entry is 
required with the NOPASSWD option set - there is currently no support for sending
the password with the sudo commands.  Public key authentication is also
supported if required.

So in short, a fresh system with openssh-server installed and a user account - nothing more!

Pepperpot can use one of two underlying SSH libraries:

* libssh2_ (recommended) - the official PHP module with packages available for most systems
  e.g. ``apt-get install libssh2-php``

* PHPSecLib_ - PHP only implementation, slow but may be your only option on windows.  
  Components need to be on the current PHP include path.

.. _libssh2: http://www.php.net/manual/en/book.ssh2.php
.. _PHPSecLib: http://phpseclib.sourceforge.net

Command line usage
==================

Create a ``machines.php`` file::

	<?php
	return array(
	  'localhost' => array('local' => true),
	  'test1' => array('host' => '10.0.0.1', 'username' => 'bob', 'password' => 'secretpass'),
     ...
	);
	?>
	
Get the os for all targets::

	> php run.php % system.os
	
Get the hostname on targets starting with 'test'::

	> php run.php test% system.hostname
   
Make sure that all webservers are running::

   > php run.php web.% service.ensure_running:apache2
   
If there is a folder called ``cache`` then ``run.php`` will persist cached values for the machines to this folder. 

API usage
=========
::

	<?php
	// a basic autoloader - use your own if required
	require_once "classes/pepperpot.php";
	PepperPot::register();
   
	// Minions are configure via an array of basic info
	$info = array('host' => '10.0.0.1', 'port' => 22, 'username' => 'bob', 'password' => 'secretpass');
	$minion = new Minion($info);
	
	// speck calls a component and caches the result as appropriate
	echo $minion->speck('system.hostname');
   
	// you can invoke actions using the task method directly
	$minion->task('service')->ensure_running('apache2');
   
	/**
	 * optionally you can store the cache for a future run and pass it as the second argument to the constructor
	 * $data = json_decode(file_get_contents('cache.dat'), true);
	 * $cache = new Minion_Cache($data);
	 * $minion = new Minion($info, $cache);
	 */
   	file_put_contents('cache.dat', json_encode($minion->cache->data()));
	?>
   
Caching
=======

During a run all values are cached for a time set by the class containing the component depending on the type of information
returned e.g. ``system.hostname`` and ``system.os`` are cached forever but ``system.uptime`` is always re-queried.  
You can manually expire a cached value by calling ``$minion->cache->delete('system.hostname')`` in the event that you have modified something
on the system.  As in the above example, the cache can be persisted between sessions which drastically reduces the number of commands
that need to be executed. ``$minion->cache->clean()`` will remove all session values and ``$minion->cache->data()`` will return an
array suitable for persisting.

In order to take advantage of the caching system you should retrieve information using the ``speck()`` interface and execute
actions/states using ``invoke()``.  Both methods take a string key as the first argument as described above and an optional
boolean to bypass the cache.

Tasks
=====

Tasks are classes that contain methods relating to a particular area of system management.  The methods can be divided into three types:

* **speck**: Returns a small piece of information about the system.  The method implementation should include a cache time settings and users should
  try to call them using the ``speck()`` interface to take advantage of the caching. Examples are ``system.os`` and ``network.mac:eth0``

* **action**: Perform a specific action.  This should be kept as small as possible, with the majority mapping to a single system call on the remote machine
  e.g. ``$minion->task('file')->chmod('/etc/motd', 0644)`` or ``$minion->task('service')->start('apache2')``

* **state**: Bring the system to a specific state.  These are more compicated methods that check existing conditions and act accordingly.  By convention they
  should be prefixed with ``ensure_`` e.g. ``service.ensure_running:apache2``.  They can make decisions based on cached values by using ``speck()`` or
  forcing a remote call.

State files
===========

These are essentially makefiles to manage dependancies for states.  If correct cache settings are used on
state methods then they can ensure a system stays in the desired state with minimal contact. (Note: some
tasks are not yet implemented)::

	state.machine:webserver:
		state.group:ubuntu-lamp
		# some machine specific stuff
		apache2.ensure_mod_enabled:userdir
		git.ensure_deployed:path/to/repo:/var/www/myproject
	
	# our generic LAMP setup
	state.group:ubuntu-lamp
		service.ready:apache2
		service.ready:mysql5
		service.ready:php5

	# a generic rule for services - will be used for apache2
	service.ready:%
		pkg.ensure_installed:%1
		service.ensure_running:%1
	
	# override generic rule for mysql5 as the service names are different	
	service.ready:mysql5
		pkg.ensure_installed:mysql-server5
		service.ensure_running:mysql-server
	
	# a virtual service
	service.ready:php5
		pkg.ensure_installed:apache2-mod-php5
		pkg.ensure_installed:php5-cli
		
You can the load and run this state file with the following command::

	> TODO

