<?php

/**
 * @file plugins/importexport/sample/SampleImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SampleImportExportPlugin
 * @ingroup plugins_importexport_sample
 *
 * @brief Sample import/export plugin
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

class SampleImportExportPlugin extends ImportExportPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		// Additional registration / initialization code
		// should go here. For example, load additional locale data:
		$this->addLocaleData();

		// This is fixed to return false so that this coding sample
		// isn't actually registered and displayed. If you're using
		// this sample for your own code, make sure you return true
		// if everything is successfully initialized.
		// return $success;
		return false;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'SampleImportExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.sample.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.sample.description');
	}

	function display($args, $request) {
		parent::display($args, $request);
		switch (array_shift($args)) {
			case 'exportIssue':
				// The actual issue export code would go here
				break;
			default:
				// Display a list of issues for export
				$journal = $request->getJournal();
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getIssues($journal->getId(), Handler::getRangeInfo(Application::get()->getRequest(), 'issues'));

				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign('issues', $issues);
				$templateMgr->display($this->getTemplateResource('issues.tpl'));
		}
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
		$this->usage($scriptName);
	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo "USAGE NOT AVAILABLE.\n"
			. "This is a sample plugin and does not actually perform a function.\n";
	}
}


