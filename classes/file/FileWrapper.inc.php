<?php

/**
 * @file classes/file/FileWrapper.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileWrapper
 * @ingroup file
 *
 * @brief Class abstracting operations for reading remote files using various protocols.
 * (for when allow_url_fopen is disabled).
 *
 * TODO:
 *     - Other protocols?
 *     - Write mode (where possible)
 */

// $Id$


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
		if ($retval = $this->open()) {
			if (is_object($retval)) { // It may be a redirect
				return $retval->contents();
			}
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
		$this->fp = null;
		$this->fp = fopen($this->url, $mode);
		return ($this->fp !== false);
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
		$info = parse_url($url);
		if (ini_get('allow_url_fopen') && Config::getVar('general', 'allow_url_fopen')) {
			$wrapper = &new FileWrapper($url, $info);
		} else {
			switch (@$info['scheme']) {
				case 'http':
					$wrapper = &new HTTPFileWrapper($url, $info);
					$wrapper->addHeader('User-Agent', 'PKP-OJS/2.x');
					break;
				case 'https':
					$wrapper = &new HTTPSFileWrapper($url, $info);
					$wrapper->addHeader('User-Agent', 'PKP-OJS/2.x');
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
	var $headers;
	var $defaultPort;
	var $defaultHost;
	var $defaultPath;
	var $redirects;

	var $proxyHost;
	var $proxyPort;
	var $proxyUsername;
	var $proxyPassword;

	function HTTPFileWrapper($url, &$info, $redirects = 5) {
		parent::FileWrapper($url, $info);
		$this->setDefaultPort(80);
		$this->setDefaultHost('localhost');
		$this->setDefaultPath('/');
		$this->redirects = 5;

		$this->proxyHost = Config::getVar('proxy', 'http_host');
		$this->proxyPort = Config::getVar('proxy', 'http_port');
		$this->proxyUsername = Config::getVar('proxy', 'proxy_username');
		$this->proxyPassword = Config::getVar('proxy', 'proxy_password');
	}

	function setDefaultPort($port) {
		$this->defaultPort = $port;
	}

	function setDefaultHost($host) {
		$this->defaultHost = $host;
	}

	function setDefaultPath($path) {
		$this->defaultPath = $path;
	}

	function addHeader($name, $value) {
		if (!isset($this->headers)) {
			$this->headers = array();
		}
		$this->headers[$name] = $value;
	}

	function open($mode = 'r') {
		$realHost = $host = isset($this->info['host']) ? $this->info['host'] : $this->defaultHost;
		$port = isset($this->info['port']) ? (int)$this->info['port'] : $this->defaultPort;
		$path = isset($this->info['path']) ? $this->info['path'] : $this->defaultPath;
		if (isset($this->info['query'])) $path .= '?' . $this->info['query'];

		if (!empty($this->proxyHost)) {
			$realHost = $host;
			$host = $this->proxyHost;
			$port = $this->proxyPort;
			if (!empty($this->proxyUsername)) {
				$this->headers['Proxy-Authorization'] = 'Basic ' . base64_encode($this->proxyUsername . ':' . $this->proxyPassword);
			}
		}

		if (!($this->fp = fsockopen($host, $port, $errno, $errstr)))
			return false;

		$additionalHeadersString = '';
		if (is_array($this->headers)) foreach ($this->headers as $name => $value) {
			$additionalHeadersString .= "$name: $value\r\n";
		}

		$request = 'GET ' . (empty($this->proxyHost)?$path:$this->url) . " HTTP/1.0\r\n" .
			"Host: $realHost\r\n" .
			$additionalHeadersString .
			"Connection: Close\r\n\r\n";
		fwrite($this->fp, $request);

		$response = fgets($this->fp, 4096);
		$rc = 0;
		sscanf($response, "HTTP/%*s %u %*[^\r\n]\r\n", $rc);
		if ($rc == 200) {
			while(fgets($this->fp, 4096) !== "\r\n");
			return true;
		}
		if(preg_match('!^3\d\d$!', $rc) && $this->redirects >= 1) {
			for($response = '', $time = time(); !feof($this->fp) && $time >= time() - 15; ) $response .= fgets($this->fp, 128);
			if (preg_match('!^(?:(?:Location)|(?:URI)|(?:location)): ([^\s]+)[\r\n]!m', $response, $matches)) {
				$this->close();
				$location = $matches[1];
				if (preg_match('!^[a-z]+://!', $location)) {
					$this->url = $location;
				} else {
					$newPath = ($this->info['path'] !== '' && strpos($location, '/') !== 0  ? dirname($this->info['path']) . '/' : (strpos($location, '/') === 0 ? '' : '/')) . $location;
					$this->info['path'] = $newPath;
					$this->url = $this->glue_url($this->info);
				}
				$returner =& FileWrapper::wrapper($this->url);
				$returner->redirects = $this->redirects - 1;
				return $returner;
			}
		}
		$this->close();
		return false;
	}

	function glue_url ($parsed) {
		// Thanks to php dot net at NOSPAM dot juamei dot com
		// See http://www.php.net/manual/en/function.parse-url.php
		if (! is_array($parsed)) return false;
		$uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
		$uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
		$uri .= isset($parsed['host']) ? $parsed['host'] : '';
		$uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
		$uri .= isset($parsed['path']) ? $parsed['path'] : '';
		$uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
		$uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
		return $uri;
	}
}

/**
 * HTTPS protocol class.
 */
class HTTPSFileWrapper extends HTTPFileWrapper {
	function HTTPSFileWrapper($url, &$info) {
		parent::HTTPFileWrapper($url, $info);
		$this->setDefaultPort(443);
		$this->setDefaultHost('ssl://localhost');
		if (isset($this->info['host'])) {
			$this->info['host'] = 'ssl://' . $this->info['host'];
		}
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
