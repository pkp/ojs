<?php

/**
 * @file classes/core/OJSApplication.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSApplication
 * @ingroup core
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

// $Id$


import('core.PKPApplication');

class OJSApplication extends PKPApplication {
	/**
	 * Get the "context depth" of this application, i.e. the number of
	 * parts of the URL after index.php that represent the context of
	 * the current request (e.g. Journal [1], or Conference and
	 * Scheduled Conference [2]).
	 * @return int
	 */
	function getContextDepth() {
		return 1;
	}

	/**
	 * Get the locale key for the name of this application.
	 * @return string
	 */
	function getNameKey() {
		return('common.openJournalSystems');
	}

	/**
	 * Determine whether or not the request is cacheable.
	 * @return boolean
	 */
	function isCacheable() {
		if (defined('SESSION_DISABLE_INIT')) return false;
		if (!Config::getVar('general', 'installed')) return false;
		if (!empty($_POST) || Validation::isLoggedIn()) return false;
		if (!Config::getVar('cache', 'web_cache')) return false;
		if (!Request::isPathInfoEnabled()) {
			$ok = array('journal', 'page', 'op', 'path');
			if (!empty($_GET) && count(array_diff(array_keys($_GET), $ok)) != 0) {
				return false;
			}
		} else {
			if (!empty($_GET)) return false;
		}

		if (in_array(Request::getRequestedPage(), array(
			'about', 'announcement', 'help', 'index', 'information', 'rt', 'issue', ''
		))) return true;

		return false;
	}

	/**
	 * Get the filename to use for cached content for the current request.
	 * @return string
	 */
	function getCacheFilename() {
		static $cacheFilename;
		if (!isset($cacheFilename)) {
			if (Request::isPathInfoEnabled()) {
				$id = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:'index';
				$id .= '-' . Locale::getLocale();
			} else {
				$id = Request::getUserVar('journal') . '-' . Request::getUserVar('page') . '-' . Request::getUserVar('op') . '-' . Request::getUserVar('path') . '-' . Locale::getLocale();
			}
			$path = dirname(dirname(dirname(__FILE__)));
			$cacheFilename = $path . '/cache/wc-' . md5($id) . '.html';
		}
		return $cacheFilename;
	}
}

?>
