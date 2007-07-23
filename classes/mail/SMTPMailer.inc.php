<?php

/**
 * @file SMTPMailer.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 * @class SMTPMailer
 *
 * Class defining a simple SMTP mail client (reference RFCs 821 and 2821).
 *
 * TODO: TLS support
 *
 * $Id$
 */

import('mail.Mail');

class SMTPMailer {

	/** @var $server string SMTP server hostname (default localhost) */
	var $server;
	
	/** @var $port string SMTP server port (default 25) */
	var $port;
	
	/** @var $auth string Authentication mechanism (optional) (PLAIN | LOGIN | CRAM-MD5 | DIGEST-MD5) */
	var $auth;
	
	/** @var $username string Username for authentication (optional) */
	var $username;
	
	/** @var $password string Password for authentication (optional) */
	var $password;

	/** @var $socket int SMTP socket */
	var $socket;
	
	/**
	 * Constructor.
	 */
	function SMTPMailer() {
		$this->server = Config::getVar('email', 'smtp_server');
		$this->port = Config::getVar('email', 'smtp_port');
		$this->auth = Config::getVar('email', 'smtp_auth');
		$this->username = Config::getVar('email', 'smtp_username');
		$this->password = Config::getVar('email', 'smtp_password');
		if (!$this->server)
			$this->server = 'localhost';
		if (!$this->port)
			$this->port = 25;
	}
	
	/**
	 * Send mail.
	 * @param $mail Mailer
	 * @param $recipients string
	 * @param $subject string
	 * @param $body string
	 * @param $headers string
	 */
	function mail(&$mail, $recipients, $subject, $body, $headers = '') {
		// Establish connection
		if (!$this->connect())
			return false;
		
		if (!$this->receive('220'))
			return $this->disconnect('Did not receive expected 220');
		
		// Send HELO/EHLO command
		if (!$this->send($this->auth ? 'EHLO' : 'HELO', Request::getServerHost()))
			return $this->disconnect('Could not send HELO/HELO');
		
		if (!$this->receive('250'))
			return $this->disconnect('Did not receive expected 250 (1)');
		
		if ($this->auth) {
			// Perform authentication
			if (!$this->authenticate())
				return $this->disconnect('Could not authenticate');
		}
		
		// Send MAIL command
		$sender = $mail->getEnvelopeSender();
		if (!isset($sender) || empty($sender)) {
			$from = $mail->getFrom();
			if (isset($from['email']) && !empty($from['email']))
				$sender = $from['email'];
			else
				$sender = get_current_user() . '@' . Request::getServerHost();
		}
		
		if (!$this->send('MAIL', 'FROM:<' . $sender . '>'))
			return $this->disconnect('Could not send sender');
		
		if (!$this->receive('250'))
			return $this->disconnect('Did not receive expected 250 (2)');
		
		// Send RCPT command(s)
		$rcpt = array();
		if (($addrs = $mail->getRecipients()) !== null)
			$rcpt = array_merge($rcpt, $addrs);
		if (($addrs = $mail->getCcs()) !== null)
			$rcpt = array_merge($rcpt, $addrs);
		if (($addrs = $mail->getBccs()) !== null)
			$rcpt = array_merge($rcpt, $addrs);
		foreach ($rcpt as $addr) {
			if (!$this->send('RCPT', 'TO:<' . $addr['email'] .'>'))
				return $this->disconnect('Could not send recipients');
			if (!$this->receive(array('250', '251')))
				return $this->disconnect('Did not receive expected 250 or 251');
		}
		
		// Send headers and body
		if (!$this->send('DATA'))
			return $this->disconnect('Could not send DATA');
		
		if (!$this->receive('354'))
			return $this->disconnect('Did not receive expected 354');
		
		if (!$this->send('To:', empty($recipients) ? 'undisclosed-recipients:;' : $recipients))
			return $this->disconnect('Could not send recipients (2)');
		
		if (!$this->send('Subject:', $subject))
			return $this->disconnect('Could not send subject');
		
		$lines = explode(MAIL_EOL, $headers);
		for ($i = 0, $num = count($lines); $i < $num; $i++) {
			if (preg_match('/^bcc:/i', $lines[$i]))
				continue;
			if (!$this->send($lines[$i]))
				return $this->disconnect('Could not send headers');
		}
		
		if (!$this->send(''))
			return $this->disconnect('Could not send CR');
		
		$lines = explode(MAIL_EOL, $body);
		for ($i = 0, $num = count($lines); $i < $num; $i++) {
			if (substr($lines[$i], 0, 1) == '.')
				$lines[$i] = '.' . $lines[$i];
			if (!$this->send($lines[$i]))
				return $this->disconnect('Could not send body');
		}
		
		// Mark end of data
		if (!$this->send('.'))
			return $this->disconnect('Could not send EOT');
		
		if (!$this->receive('250'))
			return $this->disconnect('Did not receive expected 250 (3)');
		
		// Tear down connection
		return $this->disconnect();
	}
	
	/**
	 * Connect to the SMTP server.
	 * @return boolean
	 */
	function connect() {
		$this->socket = fsockopen($this->server, $this->port, $errno, $errstr, 30);
		if (!$this->socket)
			return false;
		return true;
	}
	
	/**
	 * Disconnect from the SMTP server, sending a QUIT first.
	 * @param $success boolean
	 * @return boolean
	 */
	function disconnect($error = '') {
		if (!$this->send('QUIT') || !$this->receive('221') && empty($error)) {
			$error = 'Unable to disconnect from mail server';
		}
		fclose($this->socket);

		if (!empty($error)) {
			error_log('OJS SMTPMailer: ' . $error);
			return false;
		}
		return true;
	}
	
	/**
	 * Send a command/data.
	 * @param $command string
	 * @param $data string
	 * @return boolean
	 */
	function send($command, $data = '') {
		$ret = @fwrite($this->socket, $command . (empty($data) ? '' : ' ' . $data) . "\r\n");
		if ($ret !== false)
			return true;
		return false;
	}
	
	/**
	 * Receive a response.
	 * @param $expected string/array expected response code(s)
	 * @return boolean
	 */
	function receive($expected) {
		return $this->receiveData($expected, $data);
	}
	
	/**
	 * Receive a response and return the data payload.
	 * @param $expected string/array expected response code
	 * @param $data string buffer
	 * @return boolean
	 */
	function receiveData($expected, &$data) {
		do {
			$line = @fgets($this->socket);
		} while($line !== false && substr($line, 3, 1) != ' ');
		
		if ($line !== false) {
			$response = substr($line, 0, 3);
			$data = substr($line, 4);
			if ((is_array($expected) && in_array($response, $expected)) || ($response === $expected))
				return true;
		}
		return false;
	}
	
	/**
	 * Authenticate using the specified mechanism.
	 * @return boolean
	 */
	function authenticate() {
		switch (strtoupper($this->auth)) {
			case 'PLAIN':
				return $this->authenticate_plain();
			case 'LOGIN':
				return $this->authenticate_login();
			case 'CRAM-MD5':
				return $this->authenticate_cram_md5();
			case 'DIGEST-MD5':
				return $this->authenticate_digest_md5();
			default:
				return true;
		}	
	}
	
	/**
	 * Authenticate using PLAIN.
	 * @return boolean
	 */
	function authenticate_plain() {
		$authString = $this->username . chr(0x00) . $this->username . chr(0x00) . $this->password;
		if (!$this->send('AUTH', 'PLAIN ' . base64_encode($authString)))
			return false;
		return $this->receive('235');
	}
	
	/**
	 * Authenticate using LOGIN.
	 * @return boolean
	 */
	function authenticate_login() {
		if (!$this->send('AUTH', 'LOGIN'))
			return false;
		if (!$this->receive('334'))
			return false;
		if (!$this->send(base64_encode($this->username)))
			return false;
		if (!$this->receive('334'))
			return false;
		if (!$this->send(base64_encode($this->password)))
			return false;
		return $this->receive('235');
	}
	
	/**
	 * Authenticate using CRAM-MD5 (see RFC 2195).
	 * @return boolean
	 */
	function authenticate_cram_md5() {
		if (!$this->send('AUTH', 'CRAM-MD5'))
			return false;
		if (!$this->receiveData('334', $digest))
			return false;
		$authString = $this->username . ' ' . $this->hmac_md5(base64_decode($digest), $this->password);
		if (!$this->send(base64_encode($authString)))
			return false;
		return $this->receive('235');
	}
	
	/**
	 * Authenticate using DIGEST-MD5 (see RFC 2831).
	 * @return boolean
	 */
	function authenticate_digest_md5() {
		if (!$this->send('AUTH', 'DIGEST-MD5'))
			return false;
		if (!$this->receiveData('334', $data))
			return false;
		
		// FIXME Make parser smarter to handle "unusual" and error cases
		$challenge = array();
		$data = base64_decode($data);
		while(!empty($data)) {
			@list($key, $rest) = explode('=', $data, 2);
			if ($rest[0] != '"') {
				@list($value, $data) = explode(',', $rest, 2);
			} else {
				@list($value, $data) = explode('"', substr($rest, 1), 2);
				$data = substr($data, 1);
			}
			if (!empty($value))
				$challenge[$key] = $value;
		}
		
		$realms = explode(',', $challenge['realm']);
		if (empty($realms))
			$realm = $this->server;
		else
			$realm = $realms[0]; // FIXME Multiple realms
		$qop = 'auth';
		$nc = '00000001';
		$uri = 'smtp/' . $this->server;
		$cnonce = md5(uniqid(mt_rand(), true));
		
		$a1 = pack('H*', md5($this->username . ':' . $realm . ':' . $this->password)) . ':' . $challenge['nonce'] . ':' . $cnonce;
		
		// FIXME authorization ID not supported
		if (isset($authzid))
			$a1 .= ':' . $authzid;
		
		$a2 = 'AUTHENTICATE:' . $uri;
		
		// FIXME 'auth-int' and 'auth-conf' not supported
		if ($qop == 'auth-int' || $qop == 'auth-int')
			$a2 .= ':00000000000000000000000000000000';
		
		$response = md5(md5($a1) . ':' . ($challenge['nonce'] . ':' . $nc . ':' . $cnonce. ':' . $qop . ':' . md5($a2)));

		$authString = sprintf('charset=utf-8,username="%s",realm="%s",nonce="%s",nc=%s,cnonce="%s",digest-uri="%s",response=%s,qop=%s', $this->username, $realm, $challenge['nonce'], $nc, $cnonce, $uri, $response, $qop);
		if (!$this->send(base64_encode($authString)))
			return false;
		if (!$this->receive('334'))
			return false;
		if (!$this->send(''))
			return false;
		return $this->receive('235');
	}
	
	/**
	 * Generic HMAC digest computation (see RFC 2104).
	 * @param $hashfn string e.g., 'md5' or 'sha1'
	 * @param $blocksize int
	 * @param $data string
	 * @param $key string
	 * @return string (as hex)
	 */
	function hmac($hashfn, $blocksize, $data, $key) {
		if (strlen($key) > $blocksize)
			$key = pack('H*', $hashfn($key));
		$key = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5C), $blocksize);
		$hmac = pack('H*', $hashfn(($key ^ $opad) . pack('H*', $hashfn(($key ^ $ipad) . $data))));
		return bin2hex($hmac);
	}
	
	/**
	 * Compute HMAC-MD5 digest.
	 * @return string (as hex)
	 */
	function hmac_md5($data, $key = '') {
		return $this->hmac('md5', 64, $data, $key);
	}
	
}

?>
