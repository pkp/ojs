<?php

/**
 * @defgroup core Core
 * Core web application concerns such as routing, dispatching, etc.
 */

/**
 * @file classes/core/Core.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Core
 * @ingroup core
 *
 * @brief Class containing system-wide functions.
 */


define('PKP_LIB_PATH', 'lib' . DIRECTORY_SEPARATOR . 'pkp');
define('USER_AGENTS_FILE', Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'registry' . DIRECTORY_SEPARATOR . 'botAgents.txt');

class Core {

	/** @var array The regular expressions that will find a bot user agent */
	static $botRegexps = array();

	/**
	 * Get the path to the base installation directory.
	 * @return string
	 */
	static function getBaseDir() {
		static $baseDir;

		if (!isset($baseDir)) {
			// Need to change if the index file moves
			$baseDir = dirname(INDEX_FILE_LOCATION);
		}

		return $baseDir;
	}

	/**
	 * Sanitize a variable.
	 * Removes leading and trailing whitespace, normalizes all characters to UTF-8.
	 * @param $var string
	 * @return string
	 */
	static function cleanVar($var) {
		// only normalize strings that are not UTF-8 already, and when the system is using UTF-8
		if ( Config::getVar('i18n', 'charset_normalization') == 'On' && strtolower_codesafe(Config::getVar('i18n', 'client_charset')) == 'utf-8' && !PKPString::utf8_is_valid($var) ) {

			$var = PKPString::utf8_normalize($var);

			// convert HTML entities into valid UTF-8 characters (do not transcode)
			$var = html_entity_decode($var, ENT_COMPAT, 'UTF-8');

			// strip any invalid UTF-8 sequences
			$var = PKPString::utf8_bad_strip($var);

			$var = htmlspecialchars($var, ENT_NOQUOTES, 'UTF-8', false);
		}

		// strip any invalid ASCII control characters
		$var = PKPString::utf8_strip_ascii_ctrl($var);

		return trim($var);
	}

	/**
	 * Sanitize a value to be used in a file path.
	 * Removes any characters except alphanumeric characters, underscores, and dashes.
	 * @param $var string
	 * @return string
	 */
	static function cleanFileVar($var) {
		return PKPString::regexp_replace('/[^\w\-]/', '', $var);
	}

	/**
	 * Return the current date in ISO (YYYY-MM-DD HH:MM:SS) format.
	 * @param $ts int optional, use specified timestamp instead of current time
	 * @return string
	 */
	static function getCurrentDate($ts = null) {
		return date('Y-m-d H:i:s', isset($ts) ? $ts : time());
	}

	/**
	 * Return *nix timestamp with microseconds (in units of seconds).
	 * @return float
	 */
	static function microtime() {
		list($usec, $sec) = explode(' ', microtime());
		return (float)$sec + (float)$usec;
	}

	/**
	 * Check if the server platform is Windows.
	 * @return boolean
	 */
	static function isWindows() {
		return strtolower_codesafe(substr(PHP_OS, 0, 3)) == 'win';
	}

	/**
	 * Checks to see if a PHP module is enabled.
	 * @param $moduleName string
	 * @return boolean
	 */
	static function checkGeneralPHPModule($moduleName) {
		if (extension_loaded($moduleName)) {
			return true;
		}
		return false;
	}

	/**
	 * Check the passed user agent for a bot.
	 * @param $userAgent string
	 * @param $botRegexpsFile string An alternative file with regular
	 * expressions to find bots inside user agent strings.
	 * @return boolean
	 */
	static function isUserAgentBot($userAgent, $botRegexpsFile = USER_AGENTS_FILE) {
		static $botRegexps;
		Registry::set('currentUserAgentsFile', $botRegexpsFile);

		if (!isset($botRegexps[$botRegexpsFile])) {
			$botFileCacheId = md5($botRegexpsFile);
			$cacheManager = CacheManager::getManager();
			$cache = $cacheManager->getCache('core', $botFileCacheId, array('Core', '_botFileListCacheMiss'), CACHE_TYPE_FILE);
			$botRegexps[$botRegexpsFile] = $cache->getContents();
		}

		foreach ($botRegexps[$botRegexpsFile] as $regexp) {
			if (PKPString::regexp_match($regexp, $userAgent)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get context paths present into the passed
	 * url information.
	 * @param $urlInfo string Full url or just path info.
	 * @param $isPathInfo boolean Whether the
	 * passed url info string is a path info or not.
	 * @param $contextList array (optional)
	 * @param $contextDepth int (optional)
	 * @param $userVars array (optional) Pass GET variables
	 * if needed (for testing only).
	 * @return array
	 */
	static function getContextPaths($urlInfo, $isPathInfo, $contextList = null, $contextDepth = null, $userVars = array()) {
		$contextPaths = array();
		$application = Application::getApplication();

		if (!$contextList) {
			$contextList = $application->getContextList();
		}
		if (!$contextDepth) {
			$contextDepth = $application->getContextDepth();
		}

		// Handle context depth 0
		if (!$contextDepth) return $contextPaths;

		if ($isPathInfo) {
			// Split the path info into its constituents. Save all non-context
			// path info in $contextPaths[$contextDepth]
			// by limiting the explode statement.
			$contextPaths = explode('/', trim($urlInfo, '/'), $contextDepth + 1);
			// Remove the part of the path info that is not relevant for context (if present)
			unset($contextPaths[$contextDepth]);
		} else {
			// Retrieve context from url query string
			foreach($contextList as $key => $contextName) {
				$contextPaths[$key] = Core::_getUserVar($urlInfo, $contextName, $userVars);
			}
		}

		// Canonicalize and clean context paths
		for($key = 0; $key < $contextDepth; $key++) {
			$contextPaths[$key] = (
				isset($contextPaths[$key]) && !empty($contextPaths[$key]) ?
				$contextPaths[$key] : 'index'
			);
			$contextPaths[$key] = Core::cleanFileVar($contextPaths[$key]);
		}

		return $contextPaths;
	}

	/**
	 * Get the page present into
	 * the passed url information. It expects that urls
	 * were built using the system.
	 * @param $urlInfo string Full url or just path info.
	 * @param $isPathInfo boolean Tell if the
	 * passed url info string is a path info or not.
	 * @param $userVars array (optional) Pass GET variables
	 * if needed (for testing only).
	 * @return string
	 */
	static function getPage($urlInfo, $isPathInfo, $userVars = array()) {
		$page = Core::_getUrlComponents($urlInfo, $isPathInfo, 0, 'page', $userVars);
		return Core::cleanFileVar(is_null($page) ? '' : $page);
	}

	/**
	 * Get the operation present into
	 * the passed url information. It expects that urls
	 * were built using the system.
	 * @param $urlInfo string Full url or just path info.
	 * @param $isPathInfo boolean Tell if the
	 * passed url info string is a path info or not.
	 * @param $userVars array (optional) Pass GET variables
	 * if needed (for testing only).
	 * @return string
	 */
	static function getOp($urlInfo, $isPathInfo, $userVars = array()) {
		$operation = Core::_getUrlComponents($urlInfo, $isPathInfo, 1, 'op', $userVars);
		return Core::cleanFileVar(empty($operation) ? 'index' : $operation);
	}

	/**
	 * Get the arguments present into
	 * the passed url information (not GET/POST arguments,
	 * only arguments appended to the URL separated by "/").
	 * It expects that urls were built using the system.
	 * @param $urlInfo string Full url or just path info.
	 * @param $isPathInfo boolean Tell if the
	 * passed url info string is a path info or not.
	 * @param $userVars array (optional) Pass GET variables
	 * if needed (for testing only).
	 * @return array
	 */
	static function getArgs($urlInfo, $isPathInfo, $userVars = array()) {
		return Core::_getUrlComponents($urlInfo, $isPathInfo, 2, 'path', $userVars);
	}

 	/**
	 * Remove base url from the passed url, if any.
	 * Also, if true, checks for the context path in
	 * url and if it's missing, tries to add it.
	 * @param $url string
	 * @return string|bool The url without base url,
	 * false if it was not possible to remove it.
	 */
	function removeBaseUrl($url) {
		list($baseUrl, $contextPath) = Core::_getBaseUrlAndPath($url);

		if (!$baseUrl) return false;

		// Remove base url from url, if any.
		$url = str_replace($baseUrl, '', $url);

		// If url doesn't have the entire protocol and host part,
		// remove any possible base url path from url.
		$baseUrlPath = parse_url($baseUrl, PHP_URL_PATH);
		if ($baseUrlPath == $url) {
			// Access to the base url, no context, the entire
			// url is part of the base url and we can return empty.
			$url = '';
		} else {
			// Handle case where index.php was removed by rewrite rules,
			// and we have base url followed by the args.
			if (strpos($url, $baseUrlPath . '?') === 0) {
				$replacement = '?'; // Url path replacement.
				$baseSystemEscapedPath = preg_quote($baseUrlPath . '?', '/');
			} else {
				$replacement = '/'; // Url path replacement.
				$baseSystemEscapedPath = preg_quote($baseUrlPath . '/', '/');
			}
			$url = preg_replace('/^' . $baseSystemEscapedPath . '/', $replacement, $url);

			// Remove possible index.php page from url.
			$url = str_replace('/index.php', '', $url);
		}

		if ($contextPath) {
			// We found the contextPath using the base_url
			// config file settings. Check if the url starts
			// with the context path, if not, prepend it.
			if (strpos($url, '/' . $contextPath) !== 0) {
				$url = '/' . $contextPath . $url;
			}
		}

		// Remove any possible trailing slashes.
		$url = rtrim($url, '/');

		return $url;
	}

	/**
	 * Try to get the base url and, if configuration
	 * is set to use base url override, context
	 * path for the passed url.
	 * @param $url string
	 * @return array With two elements, base url and context path.
	 */
	function _getBaseUrlAndPath($url) {
		$baseUrl = false;
		$contextPath = false;

		// Check for override base url settings.
		$contextBaseUrls = Config::getContextBaseUrls();

		if (empty($contextBaseUrls)) {
			$baseUrl = Config::getVar('general', 'base_url');
		} else {
			// We are just interested in context base urls, remove the index one.
			if (isset($contextBaseUrls['index'])) {
				unset($contextBaseUrls['index']);
			}

			// Arrange them in length order, so we make sure
			// we get the correct one, in case there's an overlaping
			// of contexts, eg.:
			// base_url[context1] = http://somesite.com/
			// base_url[context2] = http://somesite.com/context2
			$sortedBaseUrls = array_combine($contextBaseUrls, array_map('strlen', $contextBaseUrls));
			arsort($sortedBaseUrls);

			foreach (array_keys($sortedBaseUrls) as $workingBaseUrl) {
				$urlHost = parse_url($url, PHP_URL_HOST);
				if (is_null($urlHost)) {
					// Check the base url without the host part.
					$baseUrlHost = parse_url($workingBaseUrl, PHP_URL_HOST);
					if (is_null($baseUrlHost)) break;
					$baseUrlToSearch = substr($workingBaseUrl, strpos($workingBaseUrl, $baseUrlHost) + strlen($baseUrlHost));
					// Base url with only host part, add trailing slash
					// so it can be checked below.
					if (!$baseUrlToSearch) $baseUrlToSearch = '/';
				} else {
					$baseUrlToSearch = $workingBaseUrl;
				}

				$baseUrlCheck = Core::_checkBaseUrl($baseUrlToSearch, $url);
				if (is_null($baseUrlCheck)) {
					// Can't decide. Stop searching.
					break;
				} else if ($baseUrlCheck === true) {
					$contextPath = array_search($workingBaseUrl, $contextBaseUrls);
					$baseUrl = $workingBaseUrl;
					break;
				}
			}
		}

		return array($baseUrl, $contextPath);
	}

	/**
	 * Check if the passed base url is part of
	 * the passed url, based on the context base url
	 * configuration. Both parameters can represent
	 * full url (host plus path) or just the path,
	 * but they have to be consistent.
	 * @param $baseUrl string Full base url
	 * or just it's path info.
	 * @param $url string Full url or just it's
	 * path info.
	 * @return boolean
	 */
	function _checkBaseUrl($baseUrl, $url) {
		// Check if both base url and url have host
		// component or not.
		$baseUrlHasHost = (boolean) parse_url($baseUrl, PHP_URL_HOST);
		$urlHasHost = (boolean) parse_url($url, PHP_URL_HOST);
		if ($baseUrlHasHost !== $urlHasHost) return false;

		$contextBaseUrls =& Config::getContextBaseUrls();

		// If the base url is found inside the passed url,
		// then we might found the right context path.
		if (strpos($url, $baseUrl) === 0) {
			if (strpos($url, '/index.php') == strlen($baseUrl) - 1) {
				// index.php appears right after the base url,
				// no more possible paths.
				return true;
			} else {
				// Still have to check if there is no other context
				// base url that combined with it's context path is
				// equal to this base url. If it exists, we can't
				// tell which base url is contained in url.
				foreach ($contextBaseUrls as $contextPath => $workingBaseUrl) {
					$urlToCheck = $workingBaseUrl . '/' . $contextPath;
					if (!$baseUrlHasHost) $urlToCheck = parse_url($urlToCheck, PHP_URL_PATH);
					if ($baseUrl == $urlToCheck) {
						return null;
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Bot list file cache miss fallback.
	 * @param $cache FileCache
	 * @return array:
	 */
	static function _botFileListCacheMiss($cache) {
		$id = $cache->getCacheId();
		$botRegexps = array_filter(file(Registry::get('currentUserAgentsFile')),
			array('Core', '_filterBotRegexps'));
		$cache->setEntireCache($botRegexps);
		return $botRegexps;
	}

	/**
	 * Filter the regular expressions to find bots, adding
	 * delimiters if necessary.
	 * @param $regexp string
	 */
	static function _filterBotRegexps(&$regexp) {
		$delimiter = '/';
		$regexp = trim($regexp);
		if (!empty($regexp) && $regexp[0] != '#') {
			if(strpos($regexp, $delimiter) !== 0) {
				// Make sure delimiters are in place.
				$regexp = $delimiter . $regexp . $delimiter;
			}
		} else {
			return false;
		}
		return true;
	}

	/**
	 * Get passed variable value inside the passed url.
	 * @param $url string
	 * @param $varName string
	 * @param $userVars array
	 * @return string|null
	 */
	private static function _getUserVar($url, $varName, $userVars = array()) {
		$returner = null;
		parse_str(parse_url($url, PHP_URL_QUERY), $userVarsFromUrl);
		if (isset($userVarsFromUrl[$varName])) $returner = $userVarsFromUrl[$varName];

		if (is_null($returner)) {
			// Try to retrieve from passed user vars, if any.
			if (!empty($userVars) && isset($userVars[$varName])) {
				$returner = $userVars[$varName];
			}
		}

		return $returner;
	}

	/**
	 * Get url components (page, operation and args)
	 * based on the passed offset.
	 * @param $urlInfo string
	 * @param $isPathInfo string
	 * @param $offset int
	 * @param $varName string
	 * @param $userVars array (optional) GET variables
	 * (only for testing).
	 * @return mixed array|string|null
	 */
	private static function _getUrlComponents($urlInfo, $isPathInfo, $offset, $varName = '', $userVars = array()) {
		$component = null;

		$isArrayComponent = false;
		if ($varName == 'path') {
			$isArrayComponent = true;
		}
		if ($isPathInfo) {
			$application = Application::getApplication();
			$contextDepth = $application->getContextDepth();

			$vars = explode('/', trim($urlInfo, '/'));
			if (count($vars) > $contextDepth + $offset) {
				if ($isArrayComponent) {
					$component = array_slice($vars, $contextDepth + $offset);
					for ($i=0, $count=count($component); $i<$count; $i++) {
						$component[$i] = Core::cleanVar(get_magic_quotes_gpc() ? stripslashes($component[$i]) : $component[$i]);
					}
				} else {
					$component = $vars[$contextDepth + $offset];
				}
			}
		} else {
			$component = Core::_getUserVar($urlInfo, $varName, $userVars);
		}

		if ($isArrayComponent) {
			if (empty($component)) $component = array();
			elseif (!is_array($component)) $component = array($component);
		}

		return $component;
	}
}

?>
