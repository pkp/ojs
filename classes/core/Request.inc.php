<?php

/**
 * @file classes/core/Request.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup core
 *
 * @brief Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<journal_id>/<page_name>/<operation_name>/<arguments...>
 * <journal_id> is assumed to be "index" for top-level site requests.
 */

// $Id$


import('core.PKPRequest');

class Request extends PKPRequest {
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
	 * Build a URL into OJS.
	 * @param $journalPath string Optional path for journal to use
	 * @param $page string Optional name of page to invoke
	 * @param $op string Optional name of operation to invoke
	 * @param $path mixed Optional string or array of args to pass to handler
	 * @param $params array Optional set of name => value pairs to pass as user parameters
	 * @param $anchor string Optional name of anchor to add to URL
	 * @param $escape boolean Whether or not to escape ampersands for this URL; default false.
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
				$additionalParams .= $prefix . $key . '%5B%5D=' . rawurlencode($element);
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
			$joiner = $amp . 'path%5B%5D=';
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
