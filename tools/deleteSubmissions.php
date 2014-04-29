<?php

/**
 * @file tools/deleteSubmissions.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class deleteSubmissions
 * @ingroup tools
 *
 * @brief CLI tool to delete submissions
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('classes.file.ArticleFileManager');

class SubmissionDeletionTool extends CommandLineTool {

	var $articleIds;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function SubmissionDeletionTool($argv = array()) {
		parent::CommandLineTool($argv);

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
			. "Usage: {$this->scriptName} submssion_id [...]\n";
	}

	/**
	 * Delete submission data and associated files
	 */
	function execute() {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');

		foreach($this->parameters as $articleId) {
			$article =& $articleDao->getArticle($articleId);

			if(isset($article)) {
				// remove files first, to prevent orphans
				$articleFileManager = new ArticleFileManager($articleId);

				if (! file_exists($articleFileManager->filesDir)) {
					printf("Warning: no files found for submission $articleId.\n");
				} else {
					if (! is_writable($articleFileManager->filesDir)) {
						printf("Error: Skipping submission $articleId. Can't delete files in " . $articleFileManager->filesDir . "\n");
						continue;
					} else {
						$articleFileManager->deleteArticleTree();
					}
				}

				$articleDao->deleteArticleById($articleId);
				continue;
			}
			printf("Error: Skipping $articleId. Unknown submission.\n");
		}
	}
}

$tool = new SubmissionDeletionTool(isset($argv) ? $argv : array());
$tool->execute();
?>
