<?php
/**
 * This file contains the configuration values needed for BCEncryptMail. There
 * are two basic categories: Mail and Encryption.
 */

// === MAIL ===
/**
 * The hostname to which we connect to send outgoing mail
 * @var string
 */
define("BC_SMTP_HOST", "smtp.gmail.com");
/**
 * The port on which we connect to the outgoing mail server.
 * @var int
 */
define("BC_SMTP_PORT", 587);
/**
 * Flag to indicate whether we authenticate on the outgoing mail server
 * @var boolean
 */
define("BC_SMTP_AUTH", TRUE);
/**
 * Username/Email address we must use to authenticate on the outgoing mail server.
 * @var string
 */
define("BC_SMTP_USERNAME", "email@example.com");
/**
 * Password for the outgoing mail server
 * @var string
 */
define("BC_SMTP_PASSWORD", "***YOUR EMAIL PASSWORD***");
/**
 * Flag to turn on or off the verbose comments echoed by PEAR::Mail when sending an email. Turn this on to help troubleshoot mail problems.
 * @var boolean
 */
define("BC_SMTP_DEBUG", FALSE);


// === ENCRYPTION ===
/**
 * Full path to directory where we want GPG details stored. No trailing slash. This is where our keyring will live. Do not store this in the webroot. 
 * @var string
 */
define("BC_GPG_PATH", "/path/to/gpg/dir");
