<?php
/**
 * This file checks to see if one has the necessary PHP extensions and functionality:
 * PEAR::Mail, GnuPG, etc. You can run it via command line or access it via web server.
 */

// turn off warnings and notices so they don't muck up our output.
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

// this will list necessary components that are missing 
$missing_components = array();

// check for PEAR Mail
include_once "Mail.php"; // this would ordinarily throw an E_WARNING if we didn't turn down error_reporting above

if (!class_exists("Mail")) {
	// I was easily able to get PEAR mail installed on my Ubuntu workstation with this command:
	// sudo apt-get install php-mail
	$missing_components[] = "PEAR::Mail";
}

if (!extension_loaded("gnupg")){
	// I was able to get this installed with commands listed here:
	// http://www.brandonchecketts.com/archives/gnupg-encryption-with-php-on-ubuntu-with-pecl
	$missing_components[] = "PECL GnuPG Extension";
}

$line_ending = (PHP_SAPI == "cli") ? PHP_EOL : "<br>";
if (count($missing_components) > 0){
	// some stuff is missing.
	echo "You lack one or more components: " . $line_ending;
	echo implode($line_ending, $missing_components) . $line_ending;
} else {
	echo "You are good to go!" . $line_ending;
}