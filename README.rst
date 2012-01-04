PepperPot
---------

Pepperpot is a remote execution and configuration management library written in PHP
based on the excellent python based SaltStack_.  Much of the task and
state logic has been ported almost as-is but the underlying transport is completely different.

.. _SaltStack: http://saltstack.org

Warning
=======
This is currently under development and will probably not work as expected - please check back later!

Requirements
============

In contrast to SaltStacks requirements, PepperPot minions only require the following:

1) openssh-server installed
2) a dedicated minion user (recommended)
3) sudoers entry with NOPASSWD (though basic reporting can work without this)

So in short, a fresh system with openssh-server installed and a user account - nothing more!
