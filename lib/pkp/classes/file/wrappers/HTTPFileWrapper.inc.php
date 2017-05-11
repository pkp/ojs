<?php

/**
 * @file classes/file/wrappers/HTTPFileWrapper.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file.wrappers
 * @ingroup file_wrappers
 *
 * Class providing a wrapper for the HTTP protocol.
 * (for when allow_url_fopen is disabled).
 *
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

	function __construct($url, &$info, $redirects = 5) {
		parent::__construct($url, $info);
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

		if (!($this->fp = fsockopen($host, $port)))
			return false;

		$additionalHeadersString = '';
		if (is_array($this->headers)) foreach ($this->headers as $name => $value) {
			$additionalHeadersString .= "$name: $value\r\n";
		}

		$requestHost = preg_replace("!^.*://!", "", $realHost);
		$request = 'GET ' . (empty($this->proxyHost)?$path:$this->url) . " HTTP/1.0\r\n" .
			"Host: $requestHost\r\n" .
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
		$uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower_codesafe($parsed['scheme']) == 'mailto') ? '':'//'): '';
		$uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
		$uri .= isset($parsed['host']) ? $parsed['host'] : '';
		$uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
		$uri .= isset($parsed['path']) ? $parsed['path'] : '';
		$uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
		$uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
		return $uri;
	}
}

?>
