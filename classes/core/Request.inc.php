<?php

/**
 * Request.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<journal_id>/<page_name>/<operation_name>/<arguments...>
 * <journal_id> is assumed to be "index" for top-level site requests.
 *
 * $Id$
 */

// The base script through which all requests are routed
define('INDEX_SCRIPTNAME', 'index.php');

class Request {
	
	/**
	 * Perform an HTTP redirect to an absolute or relative (to base system URL) URL.
	 * @param $url string (exclude protocol for local redirects) 
	 * @param $includeJournal boolean optional, for relative URLs will include the journal path in the redirect URL
	 */
	function redirect($url, $includeJournal = true) {
		if (!preg_match('!^https?://!i', $url)) {
			$url = Request::getIndexUrl() . '/' . ($includeJournal ? Request::getRequestedJournalPath() . '/' : '') . $url;
		}
		header("Location: $url");
		exit();
	}
	
	/**
	 * Redirect to the current URL, forcing the HTTPS protocol to be used.
	 */
	function redirectSSL() {
		Request::redirect('https://' . Request::getServerHost() . Request::getRequestPath());
	}
	
	/**
	 * Redirect to the current URL, forcing the HTTP protocol to be used.
	 */
	function redirectNonSSL() {
		Request::redirect('http://' . Request::getServerHost() . Request::getRequestPath());
	}	

	/**
	 * Get the base URL of the request (excluding script).
	 * @return string
	 */
	function getBaseUrl() {
		static $baseUrl;
		
		if (!isset($baseUrl)) {
			$baseUrl = Request::getProtocol() . '://' . Request::getServerHost() . Request::getBasePath();
		}
		
		return $baseUrl;
	}

	/**
	 * Get the base path of the request (excluding trailing slash).
	 * @return string
	 */
	function getBasePath() {
		static $basePath;
		
		if (!isset($basePath)) {
			$basePath = dirname($_SERVER['SCRIPT_NAME']);
			if ($basePath == '/') {
				$basePath = '';
			}
		}
		
		return $basePath;
	}

	/**
	 * Get the URL to the index script.
	 * @return string
	 */
	function getIndexUrl() {
		return Request::getBaseUrl() . '/' . INDEX_SCRIPTNAME;
	}

	/**
	 * Get the URL to the currently selected page (excludes other parameters).
	 * @return string
	 */
	function getPageUrl() {
		return Request::getIndexUrl() . '/' . Request::getRequestedJournalPath();
	}

	/**
	 * Get the complete URL to this page, including parameters
	 * @return string
	 */
	function getCompleteUrl() {
		$queryString = &$_SERVER['QUERY_STRING'];
		return Request::getRequestUrl() . (!empty($queryString)?"?$queryString":'');
	}

	/**
	 * Get the complete URL of the request.
	 * @return string
	 */
	function getRequestUrl() {
		static $requestUrl;
		
		if (!isset($requestUrl)) {
			$requestUrl = Request::getProtocol() . '://' . Request::getServerHost() . Request::getRequestPath();
		}
		
		return $requestUrl;
	}
	
	/**
	 * Get the completed path of the request.
	 * @return string
	 */
	function getRequestPath() {
		static $requestPath;
		if (!isset($requestPath)) {
			$requestPath = $_SERVER['SCRIPT_NAME'] . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
		}
		return $requestPath;
	}
	
	/**
	 * Get the server hostname in the request.
	 * @return string
	 */
	function getServerHost() {
		return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST']
			: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
			: (isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME']
			: 'localhost'));
	}

	/**
	 * Get the protocol used for the request (HTTP or HTTPS).
	 * @return string
	 */
	function getProtocol() {
		return (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') ? 'http' : 'https';
	}

	/**
	 * Get the remote IP address of the current request.
	 * @return string
	 */
	function getRemoteAddr() {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddr = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ipaddr = $_SERVER['REMOTE_ADDR'];
		}
		if (!isset($ipaddr) || empty($ipaddr)) {
			$ipaddr = getenv('REMOTE_ADDR');
		}
		if (!isset($ipaddr) || $ipaddr == false) {
			$ipaddr = '';
		}
		return $ipaddr;
	}
	
	/**
	 * Get the remote domain of the current request
	 * @return string
	 */
	function getRemoteDomain() {
		return getHostByAddr(Request::getRemoteAddr());
	}
	
	/**
	 * Get the user agent of the current request.
	 * @return string
	 */
	function getUserAgent() {
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		if (!isset($userAgent) || empty($userAgent)) {
			$userAgent = getenv('HTTP_USER_AGENT');
		}
		if (!isset($userAgent) || $userAgent == false) {
			$userAgent = '';
		}
		return $userAgent;
	}
	
	/**
	 * Get the journal path requested in the URL ("index" for top-level site requests).
	 * @return string 
	 */
	function getRequestedJournalPath() {
		static $journal;
		
		if (!isset($journal)) {
			$journal = '';
			if (isset($_SERVER['PATH_INFO'])) {
				$vars = explode('/', $_SERVER['PATH_INFO']);
				if (count($vars) >= 2) {
					$journal = Core::cleanFileVar($vars[1]);
				}
			}
			$journal = empty($journal) ? 'index' : $journal;
		}
		
		return $journal;
	}
	
	/**
	 * Get site data.
	 * @return Site
	 */
	 function &getSite() {
	 	static $site;
	 	
	 	if (!isset($site)) {
		 	$siteDao = &DAORegistry::getDAO('SiteDAO');
		 	$site = $siteDao->getSite();
	 	}
	 	
	 	return $site;
	 }
	
	/**
	 * Get the user session associated with the current request.
	 * @return Session
	 */
	 function &getSession() {
	 	static $session;
	 	
	 	if (!isset($session)) {
	 		$sessionManager = &SessionManager::getManager();
	 		$session = &$sessionManager->getUserSession();
	 	}
	 	
	 	return $session;
	 }
	
	/**
	 * Get the user associated with the current request.
	 * @return User
	 */
	 function &getUser() {
	 	static $user;
	 	
	 	if (!isset($user)) {
	 		$sessionManager = &SessionManager::getManager();
	 		$session = &$sessionManager->getUserSession();
	 		$user = &$session->getUser();
	 	}
	 	
	 	return $user;
	 }
	 
	/**
	 * Get the journal associated with the current request.
	 * @return Journal
	 */
	 function &getJournal() {
	 	static $journal;
	 	
	 	if (!isset($journal)) {
	 		$path = Request::getRequestedJournalPath();
	 		if ($path != 'index') {
		 		$journalDao = &DAORegistry::getDAO('JournalDAO');
		 		$journal = $journalDao->getJournalByPath(Request::getRequestedJournalPath());
		 	}
	 	}
	 	
	 	return $journal;
	 }

	/**
	 * Get the page requested in the URL.
	 * @return String the page path (under the "pages" directory)
	 */
	function getRequestedPage() {
		static $page;
		
		if (!isset($page)) {
			$page = '';
			if (isset($_SERVER['PATH_INFO'])) {
				$vars = explode('/', $_SERVER['PATH_INFO']);
				if (count($vars) >= 3) {
					$page = Core::cleanFileVar($vars[2]);
				}
			}
			$page = empty($page) || !file_exists("pages/$page") ? 'index' : $page;
		}
		
		return $page;
	}
	
	/**
	 * Get the operation requested in the URL (assumed to exist in the requested page handler).
	 * @return string
	 */
	function getRequestedOp() {
		static $op;
		
		if (!isset($op)) {
			$op = '';
			if (isset($_SERVER['PATH_INFO'])) {
				$vars = explode('/', $_SERVER['PATH_INFO']);
				if (count($vars) >= 4) {
					$op = Core::cleanFileVar($vars[3]);
				}
			}
			$op = empty($op) ? 'index' : $op;
		}
		
		return $op;
	}
	
	/**
	 * Get the arguments requested in the URL (not GET/POST arguments, only arguments prepended to the URL separated by "/").
	 * @return array
	 */
	function getRequestedArgs() {
		$args = array();
		if (isset($_SERVER['PATH_INFO'])) {
			$vars = explode('/', $_SERVER['PATH_INFO']);
			if (count($vars) > 3) {
				$args = array_slice($vars, 4);
				for ($i=0, $count=count($args); $i<$count; $i++) {
					$args[$i] = Core::cleanVar(get_magic_quotes_gpc() ? stripslashes($args[$i]) : $args[$i]);
				}
			}
		}
		return $args;	
	}
	
	/**
	 * Get the value of a GET/POST variable.
	 * @return mixed
	 */
	function getUserVar($key) {
		static $vars;
		
		if (!isset($vars)) {
			$vars = array_merge($_GET, $_POST);
		}
		
		if (isset($vars[$key])) {
			// FIXME Do not clean vars again if function is called more than once?
			Request::cleanUserVar($vars[$key]);
			return $vars[$key];
		} else {
			return null;
		}
	}

	/**
	 * Get the value of a GET/POST variable generated using the Smarty
	 * html_select_date and/or html_select_time function.
	 * @param $prefix string
	 * @param $defaultDay int
	 * @param $defaultMonth int
	 * @param $defaultYear int
	 * @param $defaultHour int
	 * @param $defaultMinute int
	 * @param $defaultSecond int
	 * @return Date
	 */
	function getUserDateVar($prefix, $defaultDay = null, $defaultMonth = null, $defaultYear = null, $defaultHour = 0, $defaultMinute = 0, $defaultSecond = 0) {
		$monthPart = Request::getUserVar($prefix . 'Month');
		$dayPart = Request::getUserVar($prefix . 'Day');
		$yearPart = Request::getUserVar($prefix . 'Year');
		$hourPart = Request::getUserVar($prefix . 'Hour');
		$minutePart = Request::getUserVar($prefix . 'Minute');
		$secondPart = Request::getUserVar($prefix . 'Second');

		if (empty($dayPart)) $dayPart = $defaultDay;
		if (empty($monthPart)) $monthPart = $defaultMonth;
		if (empty($yearPart)) $yearPart = $defaultYear;
		if (empty($hourPart)) $hourPart = $defaultHour;
		if (empty($minutePart)) $minutePart = $defaultMinute;
		if (empty($secondPart)) $secondPart = $defaultSecond;

		if (empty($monthPart) || empty($dayPart) || empty($yearPart)) return null;
		return mktime($hourPart, $minutePart, $secondPart, $monthPart, $dayPart, $yearPart);
	}

	/**
	 * Sanitize a user-submitted variable (i.e., GET/POST/Cookie variable).
	 * Strips slashes if necessary, then sanitizes variable as per Core::cleanVar().
	 * @param $var mixed
	 * @param $stripHtml boolean optional, will encode HTML if set to true
	 */
	function cleanUserVar(&$var, $stripHtml = false) {
		if (isset($var) && is_array($var)) {
			array_walk($var, create_function('&$item,$key', 'Request::cleanUserVar($item, ' . ($stripHtml ? 'true' : 'false') . ');'));
		
		} else if (isset($var)) {
			$var = Core::cleanVar(get_magic_quotes_gpc() ? stripslashes($var) : $var, $stripHtml);
			
		} else {
			return null;
		}
	}
	
	/**
	 * Get the value of a cookie variable.
	 * @return mixed
	 */
	function getCookieVar($key) {
		if (isset($_COOKIE[$key])) {
			$value = $_COOKIE[$key];
			Request::cleanUserVar($value);
			return $value;
		} else {
			return null;
		}
	}
	
	/**
	 * Set a cookie variable.
	 * @param $key string
	 * @param $value mixed
	 */
	function setCookieVar($key, $value) {
		setcookie($key, $value, 0, Request::getBasePath());
		$_COOKIE[$key] = $value;
	}
	
}

?>
