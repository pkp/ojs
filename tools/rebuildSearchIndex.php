<?php

/**
 * @file tools/rebuildSearchIndex.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * Constructor
	 * @param $argv array
	 */
	function __construct($argv) {
		parent::__construct($argv);
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to rebuild article search index\n"
			. "Usage: {$this->scriptName} [options] [journal_path]\n\n"
			. "options: The standard index implementation does\n"
			. "         not support any options. For other\n"
			. "         implementations please see the corresponding\n"
			. "         plugin documentation (e.g. 'plugins/generic/\n"
			. "         lucene/README').\n";
	}

	/**
	 * Rebuild the search index for all articles in all journals.
	 */
	function execute() {
		// Check whether we have (optional) switches.
		$switches = array();
		while (count($this->argv) && substr($this->argv[0], 0, 1) == '-') {
			$switches[] = array_shift($this->argv);
		}

		// If we have another argument that this must be a journal path.
		$journal = null;
		if (count($this->argv)) {
			$journalPath = array_shift($this->argv);
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getByPath($journalPath);
			if (!$journal) {
				die (__('search.cli.rebuildIndex.unknownJournal', array('journalPath' => $journalPath)). "\n");
			}
		}

		// Register a router hook so that we can construct
		// useful URLs to journal content.
		HookRegistry::register('Request::getBaseUrl', array($this, 'callbackBaseUrl'));

		// Let the search implementation re-build the index.
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->rebuildIndex(true, $journal, $switches);
	}

	/**
	 * Callback to patch the base URL which will be required
	 * when constructing galley/supp file download URLs.
	 * @see PKPRequest::getBaseUrl()
	 */
	function callbackBaseUrl($hookName, &$params) {
		$baseUrl =& $params[0];
		$baseUrl = Config::getVar('general', 'base_url');
		return true;
	}
}

$tool = new rebuildSearchIndex(isset($argv) ? $argv : array());
$tool->execute();

