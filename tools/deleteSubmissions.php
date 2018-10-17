<?php

/**
 * @file tools/deleteSubmissions.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class deleteSubmissions
 * @ingroup tools
 *
 * @brief CLI tool to delete submissions
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

class SubmissionDeletionTool extends CommandLineTool {

	var $articleIds;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (!sizeof($this->argv)) {
			$this->usage();
			exit(1);
		}

		$this->parameters = $this->argv;
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Permanently removes submission(s) and associated information.  USE WITH CARE.\n"
			. "Usage: {$this->scriptName} submission_id [...]\n";
	}

	/**
	 * Delete submission data and associated files
	 */
	function execute() {
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		foreach($this->parameters as $articleId) {
			$article = $articleDao->getById($articleId);
			if(!isset($article)) {
				printf("Error: Skipping $articleId. Unknown submission.\n");
				continue;
			}
			$articleDao->deleteById($articleId);
		}
	}
}

$tool = new SubmissionDeletionTool(isset($argv) ? $argv : array());
$tool->execute();

