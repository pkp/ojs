<?php

/**
 * FileWrapper.inc.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Class abstracting operations for reading remote files using various protocols.
 * (for when allow_url_fopen is disabled).
 *
 * TODO:
 *     - HTTPS
 *     - Other protocols?
 *     - Write mode (where possible)
 *
 * $Id$
 */
 
class FileWrapper {
	
	/** @var $url string URL to the file */
	var $url;
	
	/** @var $info array parsed URL info */
	var $info;
	
	/** @var $fp int the file descriptor */
	var $fp;
	
	/**
	 * Constructor.
	 * @param $url string
	 * @param $info array
	 */
	function FileWrapper($url, &$info) {
		$this->url = $url;
		$this->info = $info;
	}
	
	/**
	 * Read and return the contents of the file (like file_get_contents()).
	 * @return string
	 */
	function contents() {
		$contents = '';
		if ($this->open()) {
			while (!$this->eof())
				$contents .= $this->read();
			$this->close();
		}
		return $contents;
	}
	
	/**
	 * Open the file.
	 * @param $mode string only 'r' (read-only) is currently supported
	 * @return boolean
	 */
	function open($mode = 'r') {
		$this->fp = fopen($this->url, $mode);
		return $this->fp;
	}
	
	/**
	 * Close the file.
	 */
	function close() {
		fclose($this->fp);
		unset($this->fp);
	}
	
	/**
	 * Read from the file.
	 * @param $len int
	 * @return string
	 */
	function read($len = 8192) {
		return fread($this->fp, $len);
	}
	
	/**
	 * Check for end-of-file.
	 * @return boolean
	 */
	function eof() {
		return feof($this->fp);
	}
	
	
	//
	// Static
	//
	
	/**
	 * Return instance of a class for reading the specified URL.
	 * @param $url string
	 * @return FileWrapper
	 */
	function &wrapper($url) {
		if (ini_get('allow_url_fopen')) {
			$wrapper = &new FileWrapper($url, $info);
		} else {
			$info = parse_url($url);
			switch (@$info['scheme']) {
				case 'http':
					$wrapper = &new HTTPFileWrapper($url, $info);
					break;
				case 'ftp':
					$wrapper = &new FTPFileWrapper($url, $info);
					break;
				default:
					$wrapper = &new FileWrapper($url, $info);
			}
		}
		
		return $wrapper;
	}
}


/**
 * HTTP protocol class.
 */
class HTTPFileWrapper extends FileWrapper {
	
	function open($mode = 'r') {
		$host = isset($this->info['host']) ? $this->info['host'] : 'localhost';
		$port = isset($this->info['port']) ? (int)$this->info['port'] : 80;
		$path = isset($this->info['path']) ? $this->info['path'] : '/';
		
		if (!($this->fp = fsockopen($host, $port, $errno, $errstr)))
			return false;
		
		$request = "GET $path HTTP/1.1\r\n" .
			"Host: $host\r\n" .
			"Connection: Close\r\n\r\n";
		fwrite($this->fp, $request);
		
		$response = fgets($this->fp, 4096);
		$rc = 0;
		sscanf($response, "HTTP/%*s %u %*[^\r\n]\r\n", $rc);
		if ($rc == 200) {
			while(fgets($this->fp, 4096) !== "\r\n");
			return true;
		}
		$this->close();
		return false;
	}
}

	
/**
 * FTP protocol class.
 */
class FTPFileWrapper extends FileWrapper {

	var $ctrl;

	function open($mode = 'r') {
		$user = isset($this->info['user']) ? $this->info['user'] : 'anonymous';
		$pass = isset($this->info['pass']) ? $this->info['pass'] : 'user@example.com';
		$host = isset($this->info['host']) ? $this->info['host'] : 'localhost';
		$port = isset($this->info['port']) ? (int)$this->info['port'] : 21;
		$path = isset($this->info['path']) ? $this->info['path'] : '/';
		
		if (!($this->ctrl = fsockopen($host, $port, $errno, $errstr)))
			return false;
		
		if ($this->_open($user, $pass, $path))
			return true;

		$this->close();
		return false;
	}
	
	function close() {
		if ($this->fp) {
			parent::close();
			$rc = $this->_receive(); // FIXME Check rc == 226 ?
		}
		
		$this->_send('QUIT'); // FIXME Check rc == 221?
		$rc = $this->_receive();
		
		fclose($this->ctrl);
		$this->ctrl = null;
	}
	
	function _open($user, $pass, $path) {
		// Connection establishment
		if ($this->_receive() != '220')
			return false;

		// Authentication
		$this->_send('USER', $user);
		$rc = $this->_receive();
		if ($rc == '331') {
			$this->_send('PASS', $pass);	
			$rc = $this->_receive();
		}
		if ($rc != '230')
			return false;
			
		// Binary transfer mode
		$this->_send('TYPE', 'I');
		if ($this->_receive() != '200')
			return false;
		
		// Enter passive mode and open data transfer connection
		$this->_send('PASV');
		if ($this->_receiveLine($line) != '227')
			return false;
		
		if (!preg_match('/(\d+),(\d+),(\d+),(\d+),(\d+),(\d+)/', $line, $matches))
			return false;
		list($tmp, $h1, $h2, $h3, $h4, $p1, $p2) = $matches;
		
		$host = "$h1.$h2.$h3.$h4";
		$port = ($p1 << 8) + $p2;
		
		if (!($this->fp = fsockopen($host, $port, $errno, $errstr)))
			return false;
		
		// Retrieve file
		$this->_send('RETR', $path);
		$rc = $this->_receive();
		if ($rc != '125' && $rc != '150')
			return false;
		
		return true;
	}
	
	function _send($command, $data = '') {
		return fwrite($this->ctrl, $command . (empty($data) ? '' : ' ' . $data) . "\r\n");
	}
	
	function _receive() {
		return $this->_receiveLine($line);
	}
	
	function _receiveLine(&$line) {
 		do {
 			$line = fgets($this->ctrl);
 		} while($line !== false && ($tmp = substr(trim($line), 3, 1)) != ' ' && $tmp != '');
 		
 		if ($line !== false) {
 			return substr($line, 0, 3);
 		}
 		return false;
	}
}

?>
