<?php

/**
 * @file pages/about/HelpHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HelpHandler
 * @ingroup pages_help
 *
 * @brief Handle requests for help functions.
 */

import('classes.handler.Handler');

class HelpHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
	}

	/**
	 * Display help.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		require_once('lib/pkp/lib/vendor/michelf/php-markdown/Michelf/Markdown.inc.php');
		$path = 'docs/manual/';
		$filename = join('/', $request->getRequestedArgs());

		// If a hash (anchor) was specified, discard it -- we don't need it here.
		if ($hashIndex = strpos($filename, '#')) {
			$hash = substr($filename, $hashIndex+1);
			$filename = substr($filename, 0, $hashIndex);
		} else {
			$hash = null;
		}

		$language = AppLocale::getIso1FromLocale(AppLocale::getLocale());
		if (!file_exists($path . $language)) $language = 'en'; // Default

		if (!$filename || !preg_match('#^([[a-zA-Z0-9_-]+/)+[a-zA-Z0-9_-]+\.\w+$#', $filename) || !file_exists($path . $filename)) {
			$request->redirect(null, null, null, array($language, 'SUMMARY.md'));
		}

		// Use the summary document to find next/previous links.
		// (Yes, we're grepping markdown outside the parser, but this is much faster.)
		$previousLink = $nextLink = null;
		if (preg_match_all('/\(([^)]+\.md)\)/sm', file_get_contents($path . $language . '/SUMMARY.md'), $matches)) {
			$matches = $matches[1];
			if (($i = array_search(substr($filename, strpos($filename, '/')+1), $matches)) !== false) {
				if ($i>0) $previousLink = $matches[$i-1];
				if ($i<count($matches)-1) $nextLink = $matches[$i+1];
			}
		}

		// Use a URL filter to prepend the current path to relative URLs.
		$parser = new \Michelf\Markdown;
		$parser->url_filter_func = function ($url) use ($filename) {
			return dirname($filename) . '/' . $url;
		};
		return new JSONMessage(
			true,
			array(
				'content' => $parser->transform(file_get_contents($path . $filename)),
				'previous' => $previousLink,
				'next' => $nextLink,
			)
		);
	}
}

?>
