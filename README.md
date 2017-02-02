PHP-FindMyiPhone
================

PHP package to locate, play sounds, and lock iOS devices


What is the purpose?
====================

Since 2010, I've been logging my location history every 20 minutes. I find it to be really cool to look at a map showing me everywhere I have gone in the past few years.

![Imgur](http://i.imgur.com/YA9qgau.png)


Why should I use your code?
===========================

I have a vested interest in this code continuing to work so I'll be very quick to figure out why things stop working (which they do from time to time when Apple changes things) and I'll be quick to fix things.

How exactly does this code work?
================================

I reverse engineered the API that the FindMyiPhone app uses and that allowed me to write this code. I found this really awesome piece of software called [Charles Web Debugging Proxy](http://www.charlesproxy.com) that lets you look at the data an iPhone app sends and receives. Take a look at [example.php](https://github.com/albeebe/PHP-FindMyiPhone/blob/master/example.php) for some sample code to get you up and running. You just need to enter your username and password.

What things can I do with this code?
====================================

Currently you can
- Get the current location for any of your devices
- Play a sound and display a message on any of your devices
- Remotely lock and display a message and phone number on any of your devices

Whats the simplest piece of code to get up and running
======================================================

```php
<?php
	require_once __DIR__ . '/vendor/autoload.php';
	$fmi = new FindMyiPhone\Client("icloud_username", "icloud_password");
	$fmi->printDevices();
?>
```
