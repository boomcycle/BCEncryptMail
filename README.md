# BCEncryptMail
This project makes it easy to send an encrypted message from a PHP script. It makes use of the gnupg PECL extension to encrypt the message and it makes use of PEAR::Mail to send the email. The default settings work well for sending email via gmail accounts. The methods defined in the BCEncryptMail class should make it easy for you to manage a few keys. You'll be able to safely send a message from a PHP script such that its body cannot be read by anyone who might intercept it. BCEncryptMail cannot conceal the sender, the recipient, or the subject of the message, but its body will be securely encrypted using Gnu Privacy Guard. A rather neat aspect of this encryption technology is that it enables encryption of data such that no keys or data on the server itself can be used to decrypt the data. If you insist on emailing sensitive data from your server, you should be using a tool like this one to encrypt the message in transit.

## Prerequisites
This project requires two easily acquired extensions to PHP in order to function properly. The basic requirements are:
* Linux - This may work on OSX or Windows, but I have no tested it.
* PHP 5 or greater - Because I use OOP features of the language.
* The [GnuPG PECL extension for PHP](http://php.net/manual/en/book.gnupg.php) - It should be quite easy to install this package on most linux distros. You can also compile from scratch if you must. More detail below.
* [PEAR::Mail](http://pear.php.net/package/Mail) - Installing this on a modern linux distro is also a piece of cake. Alternatively, you can also manually download files.
* A mail client capable of deciphering PGP-encrypted messages. I am using [Thunderbird with the Enigmail add-on](https://addons.mozilla.org/en-US/thunderbird/addon/enigmail/). Wilder Security offers a short list of [tools for MS Outlook](http://www.wilderssecurity.com/threads/pgp-options-for-ms-outlook-under-windows-xp-vista-and-7.288331/) which I have not tried.

If you are anxious to know quickly if this will work on your machine, check out the file prerequisite_check.php. This script should give you an immediate idea of whether this will work or if you need to install some things.

## Overview
A general understanding of how [public-key encryption](http://en.wikipedia.org/wiki/Public-key_cryptography) works will be extremely beneficial if you are planning to use BCEncryptMail. Without a basic understanding, you may find it confusing. A few basic concepts are important:
* To effect two-way PGP-encrypted communications, you and your remote correspondent must both generate a *key pair*. To send someone an encrypted message, you will need their *public key*.
* The GnuPG PECL extension for PHP assumes the presence of a *keyring*. This is essentially a directory where keys are stored. This aspect of the GnuPG functionality makes it a bit unusual for PHP coding and you'll need to gain some familiarity with how to list the keys in your keyring and identify the key you want to use for a particular purpose. Some keys are for signing, some for encrypting. You'll need to import into your keyring the public keys of anyone to whom you plan to send encrypted messages.
* The best encryption in the world is useless if you are careless when managing your keys.
* Encryption does not magically mean security for your data. Real security is a process.

## Generating a Key Pair
There are many tools that can generate an encryption key pair. On Ubuntu, you can generate a key pair with this command:
```
gpg --homedir /path/to/gpg/dir --gen-key
```
This will result in a key pair being added to the keyring in the specified directory. If no keyring exists there yet, one will be created. This command will prompt you for various details:
```
$ gpg --homedir /path/to/gpg/dir --gen-key
gpg: WARNING: unsafe permissions on homedir `/path/to/gpg/dir'
gpg (GnuPG) 1.4.11; Copyright (C) 2010 Free Software Foundation, Inc.
This is free software: you are free to change and redistribute it.
There is NO WARRANTY, to the extent permitted by law.

gpg: keyring `/path/to/gpg/dir/secring.gpg' created
gpg: keyring `/path/to/gpg/dir/pubring.gpg' created
Please select what kind of key you want:
   (1) RSA and RSA (default)
   (2) DSA and Elgamal
   (3) DSA (sign only)
   (4) RSA (sign only)
Your selection? 2
DSA keys may be between 1024 and 3072 bits long.
What keysize do you want? (2048) 2048
Requested keysize is 2048 bits
Please specify how long the key should be valid.
         0 = key does not expire
      <n>  = key expires in n days
      <n>w = key expires in n weeks
      <n>m = key expires in n months
      <n>y = key expires in n years
Key is valid for? (0) 5y
Key expires at Sun 29 Sep 2019 08:56:22 AM PDT
Is this correct? (y/N) y

You need a user ID to identify your key; the software constructs the user ID
from the Real Name, Comment and Email Address in this form:
    "Heinrich Heine (Der Dichter) <heinrichh@duesseldorf.de>"

Real name: Joe Test
Email address: joe@example.com
Comment: This is my first key!
You selected this USER-ID:
    "Joe Test (This is my first key!) <joe@example.com>"

Change (N)ame, (C)omment, (E)mail or (O)kay/(Q)uit? o
You need a Passphrase to protect your secret key.

passphrase not correctly repeated; try again.
We need to generate a lot of random bytes. It is a good idea to perform
some other action (type on the keyboard, move the mouse, utilize the
disks) during the prime generation; this gives the random number
generator a better chance to gain enough entropy.
gpg: WARNING: some OpenPGP programs can't handle a DSA key with this digest size
+++++....+++++...++++++++++.++++++++++.++++++++++.+++++..++++++++++++++++++++++++++++++.++++++++++.+++++.+++++.++++++++++++++++++++++++++++++..+++++.+++++>.+++++>+++++...................................................<.....+++++.>+++++<+++++..........................>+++++....................................................................................................................>..+++++....................................+++++

Not enough random bytes available.  Please do some other work to give
the OS a chance to collect more entropy! (Need 169 more bytes)
We need to generate a lot of random bytes. It is a good idea to perform
some other action (type on the keyboard, move the mouse, utilize the
disks) during the prime generation; this gives the random number
generator a better chance to gain enough entropy.
+++++.+++++.++++++++++.+++++++++++++++++++++++++..+++++++++++++++..+++++...++++++++++.++++++++++++++++++++.+++++.++++++++++.+++++.++++++++++++++++++++++++++++++>++++++++++>+++++......................................................................................................................................................................................................+++++^^^
gpg: /path/to/gpg/dir/trustdb.gpg: trustdb created
gpg: key 9673FA94 marked as ultimately trusted
public and secret key created and signed.

gpg: checking the trustdb
gpg: 3 marginal(s) needed, 1 complete(s) needed, PGP trust model
gpg: depth: 0  valid:   1  signed:   0  trust: 0-, 0q, 0n, 0m, 0f, 1u
gpg: next trustdb check due at 2019-09-29
pub   2048D/9673FA94 2014-09-30 [expires: 2019-09-29]
      Key fingerprint = DC76 6F4B 81E0 B21F F9DE  07D1 4E1D 73A9 9673 FA94
uid                  Joe Test (This is my first key!) <joe@example.com>
sub   2048g/A24CA6A8 2014-09-30 [expires: 2019-09-29]
```

*NOTE* that I chose option #2, "DSA and Elgamal" and a key length of 2048 bits. Also note the Key fingerprint *DC76 6F4B 81E0 B21F F9DE  07D1 4E1D 73A9 9673 FA94*. Make sure you remember the passphrase you chose. You'll need to supply it when you decrypt or sign anything with your private key.

The result of this command is to create a keyring in this location:
```
/path/to/gpg/dir
```
Remember this path you specified because you'll need it in your PHP code.

## Using BCEncryptMail to List Keys
Now that you have established a keyring at */path/to/gpg/dir*, you should be able to use that path and the BCEncryptMail class to examine your keys, import keys, export keys, etc.

### List Keys That Can Encrypt
This example used BCEncryptMail to list the keys in your keyring that can be used to encrypt a message. It returns an associative array indexed by key *fingerprint*.
```php
<?php
require_once "BCEncryptMail.php"
$cm = new BCEncryptMail("/path/to/gpg/dir");
var_dump($cm->get_encrypt_key_select_array());
?>
```

### List Keys That Can Sign
This example used BCEncryptMail to list the keys in your keyring that can be used to sign a message indexed by key fingerprint.
```php
<?php
require_once "BCEncryptMail.php";
$cm = new BCEncryptMail("/path/to/gpg/dir");
var_dump($cm->get_sign_key_select_array());
?>
```

### Import Someone's Public Key
To send an encrypted message to someone, you need their public key. You'll need to ask them to send it to you. Or you can check a key server. For example, I searched the MIT key server for "Linux Torvalds" and located [this key](https://pgp.mit.edu/pks/lookup?op=get&search=0x79BE3E4300411886). Note the very specific formatting. You can import this signature to your keyring by saving it as a textfile and using BCEncryptMail. I saved it to /tmp/linus.txt.
```php
<?php
require_once "BCEncryptMail.php";
$cm = new BCEncryptMail("/path/to/gpg/dir");
var_dump($cm->import_key_file("/tmp/linux.txt"));
?>
```
The output of this script is an associative array containing a summary of the import operation. You'll note that it reports a key fingerprint for the imported public key: *ABAF11C65A2970B130ABE3C479BE3E4300411886*.

### Encrypting a Message
To send an encrypted message, we must specify the key of the recipient. We'll just use the key that was reported when we imported Linus' Key:
```php
<?php
require_once "BCEncryptMail.php";
$cm = new BCEncryptMail("/path/to/gpg/dir");
$plaintext = "Here is my SUPER SECRET MESSAGE!  SHHHH! DON'T TELL ANYONE";
$ciphertext = $cm->encrypt_message("ABAF11C65A2970B130ABE3C479BE3E4300411886", $plaintext);
var_dump($ciphertext);
?>
```

### Encrypting and Signing a Message
Signing a message should provide assurance to the recipient that it was sent by you if your recipient has your public key to verify your signature. In this code example, I'm specifying the fingerprint of linus' key for encrypting the message and providing the fingerprint of my previous generated keypair for the signing. I also have to supply the password that I used when I created my keypair in the step above. 
```php
<?php
require_once "BCEncryptMail.php";
$cm = new BCEncryptMail("/path/to/gpg/dir");
$plaintext = "Here is my SUPER SECRET MESSAGE!  SHHHH! DON'T TELL ANYONE";
$ciphertext = $cm->encrypt_and_sign_message("ABAF11C65A2970B130ABE3C479BE3E4300411886", $plaintext, "DC766F4B81E0B21FF9DE07D14E1D73A99673FA94", "!!!MY PASSPHRASE!!!");
var_dump($ciphertext);
?>
```

### Encrypting and Emailing a Message.
In addition to the path we supply to our BCEncryptMail constructor which tells it where to locate our encryption credentials, we must also specify mail credentials if we want to use this class to send the mail. I chose to add mail functions to this class because getting mail to work properly can be a real chore sometimes. Hopefully it will save someone a lot of trial and error.

In this example, I have used the constants defined in config.php. The default values in there should work well with gmail. You will, of course, need to supply your own email address, email password, etc.
```php
<?php
require_once "BCEncryptMail.php";
require_once "config.php";
$cm = new BCEncryptMail(BC_GPG_PATH);
$cm->configure_smtp(
		BC_SMTP_HOST,
		BC_SMTP_PORT,
		BC_SMTP_AUTH,
		BC_SMTP_USERNAME,
		BC_SMTP_PASSWORD,
		BC_SMTP_DEBUG
);
$plaintext = "Here is my SUPER SECRET MESSAGE!  SHHHH! DON'T TELL ANYONE";
var_dump($cm->send_encrypted_email("recipient@example.com", "ABAF11C65A2970B130ABE3C479BE3E4300411886", "sender@example.com", "A signed, encrypted message", $plaintext));
?>
```


## Helpful Links
- [Using GnuPG with PHP](http://devzone.zend.com/1278/using-gnupg-with-php/) - Article from Zend that describes how to install the gnupg PECL extension and other helpful details about GnuPG
- [GnuPG Encryption with PHP](http://www.brandonchecketts.com/archives/gnupg-encryption-with-php) - Blog article that provides alternate installation tactics and additional detail about generating a signing keY for apache. NOTE: the pecl install instructions didn't work for me on Ubuntu.
- [GnuPG Encryption with PHP on Ubuntu with PECL](http://www.brandonchecketts.com/archives/gnupg-encryption-with-php-on-ubuntu-with-pecl) - I used the commands in this blog post to effectively install the GnuPG PECL extension.
