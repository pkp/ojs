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
	function redirectUrl($url) {
		if (HookRegistry::call('Request::redirect', array(&$url))) {
			return;
		}
		
		header("Location: $url");
		exit();
	}

	/**
	 * Redirect to the specified page within OJS. Shorthand for a common call to Request::redirect(Request::url(...)).
	 * @param $journalPath string The path of the journal to redirect to.
	 * @param $page string The name of the op to redirect to.
	 * @param $op string optional The name of the op to redirect to.
	 * @param $path mixed string or array containing path info for redirect.
	 * @param $params array Map of name => value pairs for additional parameters
	 * @param $anchor string Name of desired anchor on the target page
	 */
	function redirect($journalPath = null, $page = null, $op = null, $path = null, $params = null, $anchor = null) {
		Request::redirectUrl(Request::url($journalPath, $page, $op, $path, $params, $anchor));
	}

	/**
	 * Redirect to the current URL, forcing the HTTPS protocol to be used.
	 */
	function redirectSSL() {
		$url = 'https://' . Request::getServerHost() . Request::getRequestPath();
		$queryString = Request::getQueryString();
		if (!empty($queryString)) $url .= "?$queryString";
		Request::redirectUrl($url);
	}
	
	/**
	 * Redirect to the current URL, forcing the HTTP protocol to be used.
	 */
	function redirectNonSSL() {
		$url = 'http://' . Request::getServerHost() . Request::getRequestPath();
		$queryString = Request::getQueryString();
		if (!empty($queryString)) $url .= "?$queryString";
		if (HookRegistry::call('Request::redirectNonSSL', array(&$url))) {
			return;
		}
		Request::redirectUrl($url);
	}	

	/**
	 * Get the base URL of the request (excluding script).
	 * @return string
	 */
	function getBaseUrl() {
		static $baseUrl;
		
		if (!isset($baseUrl)) {
			$baseUrl = Request::getProtocol() . '://' . Request::getServerHost() . Request::getBasePath();
			HookRegistry::call('Request::getBaseUrl', array(&$baseUrl));
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
			HookRegistry::call('Request::getBasePath', array(&$basePath));
		}
		
		return $basePath;
	}

	/**
	 * Get the URL to the index script.
	 * @return string
	 */
	function getIndexUrl() {
		static $indexUrl;

		if (!isset($indexUrl)) {
			$indexUrl = Request::getBaseUrl() . '/' . INDEX_SCRIPTNAME;
			HookRegistry::call('Request::getIndexUrl', array(&$indexUrl));
		}

		return $indexUrl;
	}

	/**
	 * Get the complete URL to this page, including parameters.
	 * @return string
	 */
	function getCompleteUrl() {
		static $completeUrl;

		if (!isset($completeUrl)) {
			$completeUrl = Request::getRequestUrl();
			$queryString = Request::getQueryString();
			if (!empty($queryString)) $completeUrl .= "?$queryString";
			HookRegistry::call('Request::getCompleteUrl', array(&$completeUrl));
		}

		return $completeUrl;
	}

	/**
	 * Get the complete URL of the request.
	 * @return string
	 */
	function getRequestUrl() {
		static $requestUrl;
		
		if (!isset($requestUrl)) {
			$requestUrl = Request::getProtocol() . '://' . Request::getServerHost() . Request::getRequestPath();
			HookRegistry::call('Request::getRequestUrl', array(&$requestUrl));
		}
		
		return $requestUrl;
	}

	/**
	 * Get the complete set of URL parameters to the current request.
	 * @return string
	 */
	function getQueryString() {
		static $queryString;

		if (!isset($queryString)) {
			$queryString = isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:'';
			HookRegistry::call('Request::getQueryString', array(&$queryString));
		}

		return $queryString;
	}

	/**
	 * Get the completed path of the request.
	 * @return string
	 */
	function getRequestPath() {
		static $requestPath;
		if (!isset($requestPath)) {
			$requestPath = $_SERVER['SCRIPT_NAME'];
			if (Request::isPathInfoEnabled()) {
				$requestPath .= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
			}
			HookRegistry::call('Request::getRequestPath', array(&$requestPath));
		}
		return $requestPath;
	}
	
	/**
	 * Get the server hostname in the request.
	 * @return string
	 */
	function getServerHost() {
		static $serverHost;
		if (!isset($serverHost)) {
			$serverHost = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST']
				: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
				: (isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME']
				: 'localhost'));
			HookRegistry::call('Request::getServerHost', array(&$serverHost));
		}
		return $serverHost;
	}

	/**
	 * Get the protocol used for the request (HTTP or HTTPS).
	 * @return string
	 */
	function getProtocol() {
		static $protocol;
		if (!isset($protocol)) {
			$protocol = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') ? 'http' : 'https';
			HookRegistry::call('Request::getProtocol', array(&$protocol));
		}
		return $protocol;
	}

	/**
	 * Get the remote IP address of the current request.
	 * @return string
	 */
	function getRemoteAddr() {
		static $ipaddr;
		if (!isset($remoteAddr)) {
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
			HookRegistry::call('Request::getRemoteAddr', array(&$ipaddr));
		}
		return $ipaddr;
	}
	
	/**
	 * Get the remote domain of the current request
	 * @return string
	 */
	function getRemoteDomain() {
		static $remoteDomain;
		if (!isset($remoteDomain)) {
			$remoteDomain = getHostByAddr(Request::getRemoteAddr());
			HookRegistry::call('Request::getRemoteDomain', array(&$remoteDomain));
		}
	}
	
	/**
	 * Get the user agent of the current request.
	 * @return string
	 */
	function getUserAgent() {
		static $userAgent;
		if (!isset($userAgent)) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
			}
			if (!isset($userAgent) || empty($userAgent)) {
				$userAgent = getenv('HTTP_USER_AGENT');
			}
			if (!isset($userAgent) || $userAgent == false) {
				$userAgent = '';
			}
			HookRegistry::call('Request::getUserAgent', array(&$userAgent));
		}
		return $userAgent;
	}

	/**
	 * Return true iff PATH_INFO is enabled.
	 */
	function isPathInfoEnabled() {
		static $isPathInfoEnabled;
		if (!isset($isPathInfoEnabled)) {
                        $isPathInfoEnabled = Config::getVar('general', 'disable_path_info')?false:true;
                }
		return $isPathInfoEnabled;
	}

	/**
	 * Get the journal path requested in the URL ("index" for top-level site requests).
	 * @return string 
	 */
	function getRequestedJournalPath() {
		static $journal;
		
		if (!isset($journal)) {
			if (Request::isPathInfoEnabled()) {
				$journal = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 2) {
						$journal = Core::cleanFileVar($vars[1]);
					}
				}
			} else {
				$journal = Request::getUserVar('journal');
			}

			$journal = empty($journal) ? 'index' : $journal;
			HookRegistry::call('Request::getRequestedJournalPath', array(&$journal));
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
	 		$session = $sessionManager->getUserSession();
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
	 		$user = $session->getUser();
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
			if (Request::isPathInfoEnabled()) {
				$page = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 3) {
						$page = Core::cleanFileVar($vars[2]);
					}
				}
			} else {
				$page = Request::getUserVar('page');
			}
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
			if (Request::isPathInfoEnabled()) {
				$op = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 4) {
						$op = Core::cleanFileVar($vars[3]);
					}
				}
			} else {
				return Request::getUserVar('op');
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
		if (Request::isPathInfoEnabled()) {
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
		} else {
			$args = Request::getUserVar('path');
			if (empty($args)) $args = array();
			elseif (!is_array($args)) $args = array($args);
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

	/**
	 * Build a URL into OJS.
	 * @param $journalPath string Optional path for journal to use
	 * @param $page string Optional name of page to invoke
	 * @param $op string Optional name of operation to invoke
	 * @param $path mixed Optional string or array of args to pass to handler
	 * @param $params array Optional set of name => value pairs to pass as user parameters
	 * @param $anchor string Optional name of anchor to add to URL
	 * @param $escape boolean Whether or not to escape ampersands for this URL; default true.
	 */
	function url($journalPath = null, $page = null, $op = null, $path = null, $params = null, $anchor = null, $escape = false) {
		$pathInfoDisabled = !Request::isPathInfoEnabled();

		$amp = $escape?'&amp;':'&';
		$prefix = $pathInfoDisabled?$amp:'?';

		// Establish defaults for page and op
		$defaultPage = Request::getRequestedPage();
		$defaultOp = Request::getRequestedOp();

		// If a journal has been specified, don't supply default
		// page or op.
		if ($journalPath) {
			$journalPath = rawurlencode($journalPath);
			$defaultPage = null;
			$defaultOp = null;
		} else {
			$journal =& Request::getJournal();
			if ($journal) $journalPath = $journal->getPath();
			else $journalPath = 'index';
		}

		// Get overridden base URLs (if available).
		$overriddenBaseUrl = Config::getVar('general', "base_url[$journalPath]");

		// If a page has been specified, don't supply a default op.
		if ($page) {
			$page = rawurlencode($page);
			$defaultOp = null;
		} else {
			$page = $defaultPage;
		}

		// Encode the op.
		if ($op) $op = rawurlencode($op);
		else $op = $defaultOp;

		// Process additional parameters
		$additionalParams = '';
		if (!empty($params)) foreach ($params as $key => $value) {
			if (is_array($value)) foreach($value as $element) {
				$additionalParams .= $prefix . $key . '[]=' . rawurlencode($element);
				$prefix = $amp;
			} else {
				$additionalParams .= $prefix . $key . '=' . rawurlencode($value);
				$prefix = $amp;
			}
		}

		// Process anchor
		if (!empty($anchor)) $anchor = '#' . rawurlencode($anchor);
		else $anchor = '';

		if (!empty($path)) {
			if (is_array($path)) $path = array_map('rawurlencode', $path);
			else $path = array(rawurlencode($path));
			if (!$page) $page = 'index';
			if (!$op) $op = 'index';
		}

		$pathString = '';
		if ($pathInfoDisabled) {
			$joiner = $amp . 'path[]=';
			if (!empty($path)) $pathString = $joiner . implode($joiner, $path);
			if (empty($overriddenBaseUrl)) $baseParams = "?journal=$journalPath";
			else $baseParams = '';

			if (!empty($page) || !empty($overriddenBaseUrl)) {
				$baseParams .= empty($baseParams)?'?':$amp . "page=$page";
				if (!empty($op)) {
					$baseParams .= $amp . "op=$op";
				}
			}
		} else {
			if (!empty($path)) $pathString = '/' . implode('/', $path);
			if (empty($overriddenBaseUrl)) $baseParams = "/$journalPath";
			else $baseParams = '';

			if (!empty($page)) {
				$baseParams .= "/$page";
				if (!empty($op)) {
					$baseParams .= "/$op";
				}
			}
		}

		return ((empty($overriddenBaseUrl)?Request::getIndexUrl():$overriddenBaseUrl) . $baseParams . $pathString . $additionalParams . $anchor);
	}
}

?>
