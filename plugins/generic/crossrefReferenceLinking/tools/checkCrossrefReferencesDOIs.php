<?php

/**
 * @file plugins/generic/crossrefReferenceLinking/checkCrossrefReferencesDOIs.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossrefReferencesDOIsTool
 * @ingroup plugins_generic_crossrefReferenceLinking
 *
 * @brief CLI tool to check found Crossref citations DOIs
 */

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/tools/bootstrap.inc.php');

class CrossrefReferencesDOIsTool extends CommandLineTool {

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
		echo _('plugins.generic.crossrefReferenceLinking.citationsFormActionName') . "\n"
			. "Usage:\n"
			. "{$this->scriptName} all\n"
			. "{$this->scriptName} context context_id [...]\n"
			. "{$this->scriptName} submission submission_id [...]\n";
	}

	/**
	 * Check citations DOIs
	 */
	function execute() {
		$submissionDao = Application::getSubmissionDAO();
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$contextDao = Application::getContextDAO();

		switch(array_shift($this->parameters)) {
			case 'all':
				$contexts = $contextDao->getAll();
				while ($context = $contexts->next()) {
					$plugin = PluginRegistry::loadPlugin('generic', 'crossrefReferenceLinking', $context->getId());
					// Get published articles to check
					$submissionsToCheck = $plugin->getArticlesToCheck($context);
					while ($submission = $submissionsToCheck->next()) {
						$plugin->getCrossrefReferencesDOIs($submission);
					}
				}
				break;
			case 'context':
				foreach($this->parameters as $contextId) {
					$context = $contextDao->getById($contextId);
					if(!isset($context)) {
						printf("Error: Skipping $contextId. Unknown context.\n");
						continue;
					}
					$plugin = PluginRegistry::loadPlugin('generic', 'crossrefReferenceLinking', $context->getId());
					// Get published articles to check
					$submissionsToCheck = $plugin->getArticlesToCheck($context);
					while ($submission = $submissionsToCheck->next()) {
						$plugin->getCrossrefReferencesDOIs($submission);
					}
				}
				break;
			case 'submission':
				foreach($this->parameters as $submissionId) {
					$submission = $submissionDao->getById($submissionId);
					if(!isset($submission)) {
						printf("Error: Skipping $submissionId. Unknown submission.\n");
						continue;
					}
					$plugin = PluginRegistry::loadPlugin('generic', 'crossrefReferenceLinking', $submission->getContextId());
					$plugin->getCrossrefReferencesDOIs($submission);				}
				break;
			default:
				$this->usage();
				break;
		}
	}
}

$tool = new CrossrefReferencesDOIsTool(isset($argv) ? $argv : array());
$tool->execute();
?>
