<?php
/**
 * Defines a class to make mailing encrypted messages convenient and easy
 */

class BCEncryptMail {
	const GNUPGHOME = "GNUPGHOME";
	private $smtp_host;
	private $smtp_port;
	private $smtp_auth;
	private $smtp_username;
	private $smtp_password;
	private $smtp_debug;

	private $original_gpg_path;
	private $gpg_path;
	
	/**
	 * Constructor.
	 * @param string $gpg_path The path to the GPG keyring directory
	 * @throws Exception
	 */
	public function __construct($gpg_path=NULL) {
		$this->gpg_path = $gpg_path;
		if (!is_null($this->gpg_path)){
			// make sure the dir exists
			clearstatcache();
			if (!is_dir($this->gpg_path)){
 				throw new Exception("GPG path " . $this->gpg_path . " is not a valid directory. You may lack sufficient permissions.");
			}
			if (!is_readable($this->gpg_path)){
				throw new Exception("GPG path " . $this->gpg_path . " is not readable.");
			}
		}
	} // __construct()
	
	/**
	 * Sets the parameters needed to connect to an SMTP gateway in order to send email.
	 * @param string $host SMTP host. For gmail accounts, use smtp.gmail.com
	 * @param int $port SMTP port. For gmail accounts, use 587.
	 * @param boolean $auth Whether to authenticate when connecting via SMTP. Use TRUE for gmail accounts.
	 * @param string $username Your mail username. For gmail accounts, use your entire email address.
	 * @param string $password Your mail password.
	 */
	public function configure_smtp($host, $port, $auth, $username, $password, $debug=FALSE) {
		// TODO: validate these to prevent bad input.
		$this->smtp_host = $host;
		$this->smtp_port = $port;
		$this->smtp_auth = $auth;
		$this->smtp_username = $username;
		$this->smtp_password = $password;
		$this->smtp_debug = $debug;
	}
	
	/**
	 * Gets a list of keys in the GPG keyring that match the search criteria
	 * @param string $search A search string to be matched. Can be a fingerprint, email, or partial name
	 * @throws Exception
	 * @return Numerically indexed array of associative arrays, each describing a key in the keyring. @see php.net/manual/en/function.gnupg-keyinfo.php 
	 */
	public function get_key_list($search="") {
		$this->set_env();
		
		try {
			$gpg = new gnupg();
			// throw exception if error occurs
			$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
			
			$keys = $gpg->keyinfo($search);
			
			$this->restore_env();
			return $keys;
			
		} catch (Exception $e) {
			// restore the envelope
			$this->restore_env();
			// re-throw the exception
			throw $e;
		}
		
	} // get_key_list()
	
	/**
	 * Gets all the keys from the keyring that can encrypt
	 * @return multitype:string An associative array of friendly names for the keys indexed by their fingerprints
	 */
	public function get_encrypt_key_select_array() {
		$keys = $this->get_key_list();
		$retval = array();
		foreach($keys as $key) {
			foreach ($key["subkeys"] as $subkey) {
				if ($subkey["can_encrypt"]) {
					$retval[$subkey["fingerprint"]] = $this->get_key_friendly_name($key);
				}
			}
		}
		return $retval;
		
	}
	
	/**
	 * Gets all the keys from the keyring that can encrypt
	 * @return multitype:string An associative array of friendly names for the keys indexed by their fingerprints
	 */
	public function get_sign_key_select_array() {
		$keys = $this->get_key_list();
		$retval = array();
		foreach($keys as $key) {
			foreach ($key["subkeys"] as $subkey) {
				if ($subkey["can_sign"]) {
					$retval[$subkey["fingerprint"]] = $this->get_key_friendly_name($key);
				}
			}
		}
		return $retval;
	
	}
	
	
	private function get_key_friendly_name($key) {
		$retval = "[NO NAME AVAILABLE]";
		if (isset($key["uids"])) {
			if (isset($key["uids"][0])){
				if (isset($key["uids"][0]["uid"])) { 
					$retval = $key["uids"][0]["uid"];
				}
			}
		}
		
		return $retval;
	}
	
	/**
	 * Imports a key into the keyring
	 * @param string $key_data The actual ASCII contents of the key
	 * @throws Exception
	 * @return array Associative array describing success/failure/etc. of the import operation (@see php.net/manual/en/function.gnupg-import.php )
	 */
	public function import_key($key_data) {
		$this->set_env();
	
		try {
			$gpg = new gnupg();
			// throw exception if error occurs
			$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
				
			$result = $gpg->import($key_data);
				
			$this->restore_env();
			
			return $result;
				
		} catch (Exception $e) {
			// restore the envelope
			$this->restore_env();
			// re-throw the exception
			throw $e;
		}
	
	} // import_key()
	/**
	 * Imports a key from a file. The file should contain the ASCII representation of a key.
	 * @param string $path_to_file
	 * @throws Exception
	 * @return array Associative array describing success/failure/etc. of the import operation (@see php.net/manual/en/function.gnupg-import.php )
	 */
	public function import_key_file($path_to_file) {
		if (!file_exists($path_to_file)) {
			throw new Exception("The file $path_to_file does not exist");
		}
		if (!is_readable($path_to_file)){
			throw new Exception("The file $path_to_file is not readable.");
		}
		
		$key_data = file_get_contents($path_to_file);
		
		return $this->import_key($key_data);
		
	}
	
	public function export_key($key_id) {
		$this->set_env();
		
		try {
			$gpg = new gnupg();
			// throw exception if error occurs
			$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
				
			$key_data = $gpg->export($key_id);
			
			$this->restore_env();
		
			return $key_data;
		
		} catch (Exception $e) {
			// restore the envelope
			$this->restore_env();
			// re-throw the exception
			throw $e;
		}
		
	}
	
	
	public function encrypt_message($recipient_key_id, $plaintext) {
		$this->set_env();
		
		try {
			$gpg = new gnupg();
			// throw exception if error occurs
			$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
			
			$gpg->addencryptkey($recipient_key_id);
		
			$cipher_text = $gpg->encrypt($plaintext);
		
			$this->restore_env();
				
			return $cipher_text;
		
		} catch (Exception $e) {
			// restore the envelope
			$this->restore_env();
			// re-throw the exception
			throw $e;
		}
		
	} // encrypt_message()
	
	public function encrypt_and_sign_message($recipient_key_id, $plaintext, $signer_key_id, $passphrase) {
		$this->set_env();
	
		try {
			$gpg = new gnupg();
			// throw exception if error occurs
			$gpg->seterrormode(gnupg::ERROR_EXCEPTION);
				
			$gpg->addencryptkey($recipient_key_id);
			$gpg->addsignkey($signer_key_id, $passphrase);
	
			$cipher_text = $gpg->encryptsign($plaintext);
	
			$this->restore_env();
	
			return $cipher_text;
	
		} catch (Exception $e) {
			// restore the envelope
			$this->restore_env();
			// re-throw the exception
			throw $e;
		}
	
	} // encrypt_and_sign_message()
	
	/**
	 * Encrypts the supplied plaintext using the key specified by recipient_key_id and emails the cipher text
	 * @param string $recipient_email_address
	 * @param string $recipient_key_id Some string to uniquely identify the key to be used for encryption. Ideally this would be the fingerprint of the correct key
	 * @param string $sender_email_address
	 * @param string $subject Subject to appear on the email
	 * @param string $plaintext The plain text of the message to be sent
	 * @return boolean Returns TRUE if PEAR::Mail successfully sends the email
	 * @throws Exception If PEAR::Mail has a problem, an exception will be thrown.
	 */
	public function send_encrypted_email($recipient_email_address, $recipient_key_id, $sender_email_address, $subject, $plaintext) {
		
		$cipher_text = $this->encrypt_message($recipient_key_id, $plaintext);

		return $this->send_email($recipient_email_address, $sender_email_address, $subject, $cipher_text);
	}
	
	public function send_signed_encrypted_email($recipient_email_address, $recipient_key_id, $sender_email_address, $sender_key_id, $passphrase, $subject, $plaintext) {
	
		$cipher_text = $this->encrypt_and_sign_message($recipient_key_id, $plaintext, $sender_key_id, $passphrase);
		return $this->send_email($recipient_email_address, $sender_email_address, $subject, $cipher_text);
	}
	
	public function email_exported_key($key_id, $recipient_email_address, $sender_email_address, $subject) {
		$key_export_data = $this->export_key($key_id);
		return $this->send_email($recipient_email_address, $sender_email_address, $subject, $key_export_data);
	}
	
	/**
	 * Just instantiates a PEAR::Mail object and sends an email
	 * @param string $recipient_email_address
	 * @param string $sender_email_address
	 * @param string $subject
	 * @param string $message
	 * @throws Exception
	 * @return boolean
	 */
	private function send_email($recipient_email_address, $sender_email_address, $subject, $message) {
		require_once "Mail.php";
		
		$params = array(
				"host" => $this->smtp_host,
				"port" => $this->smtp_port,
				"auth" => $this->smtp_auth,
				"username" => $this->smtp_username,
				"password" => $this->smtp_password,
				"debug" => $this->smtp_debug
		);
		/**
		 * PEAR::Mail object
		 * @var Mail
		*/
		$smtp = Mail::factory('smtp', $params);
		if (PEAR::isError($smtp)){
			throw new Exception("Unable to create PEAR::Mail object");
		}
		$headers = array(
				"From" => $sender_email_address,
				"Sender" => $sender_email_address,
				"Reply-to" => $sender_email_address,
				"Subject" => $subject
		);
		
		$send_result = $smtp->send($recipient_email_address, $headers, $message);
		if ($send_result !== TRUE){
			throw new Exception("PEAR::Mail send resulted in failure");
		}
		
		return TRUE;
		
	}
	
	
	/**
	 * Takes note of the current envelope var for GNUPGHOME and applies the new gpg path 
	 */
	private function set_env() {
		if (!is_null($this->gpg_path)){
			// take a snapshot of whatever the current GPG path is
			$this->original_gpg_path = getenv(self::GNUPGHOME);
			putenv(self::GNUPGHOME . "=" . $this->gpg_path);
		}
	}
	/**
	 * Restores the envelope variables for GNUPGHOME to their prior state
	 */
	private function restore_env() {
		if (!is_null($this->gpg_path)){
			putenv(self::GNUPGHOME . "=" . $this->original_gpg_path);
		}
	}
	
} // class BCEncryptMail