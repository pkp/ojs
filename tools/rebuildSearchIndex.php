<?php

/**
 * @file tools/rebuildSearchIndex.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class rebuildSearchIndex
 * @ingroup tools
 *
 * @brief CLI tool to rebuild the article keyword search database.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('classes.search.ArticleSearchIndex');

class rebuildSearchIndex extends CommandLineTool {

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to rebuild article search index\n"
			. "Usage: {$this->scriptName}\n";
	}

	/**
	 * Rebuild the search index for all articles in all journals.
	 */
	function execute() {
		HookRegistry::register('Request::getBaseUrl', array(&$this, 'callbackBaseUrl'));
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->rebuildIndex(true);
	}

	/**
	 * Callback to patch the base URL which will be required
	 * when constructing galley/supp file download URLs.
	 * @see PKPRequest::getBaseUrl()
	 */
	function callbackBaseUrl($hookName, $params) {
		$baseUrl =& $params[0];
		$baseUrl = Config::getVar('general', 'base_url');
		return true;
	}
}

$tool = new rebuildSearchIndex(isset($argv) ? $argv : array());
$tool->execute();
?>
