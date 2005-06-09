<?php

/**
 * NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin
 *
 * $Id$
 */

import('classes.plugins.ImportExportPlugin');

import('xml.XMLWriter');

class NativeImportExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		// Additional registration / initialization code
		// should go here. For example, load additional locale data:
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'NativeImportExportPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.native.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.native.description');
	}

	function display(&$args) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pluginUrl', $this->getPluginUrl());
		switch (array_shift($args)) {
			case 'exportIssue':
				$issueId = array_shift($args);
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issue = &$issueDao->getIssueById($issueId);
				if (!$issue) Request::redirect($this->getPluginUrl());
				$this->exportIssue(&$issue);
				break;
			default:
				// Display a list of issues for export
				$journal = &Request::getJournal();
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getIssues($journal->getJournalId(), Handler::getRangeInfo('issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
		}
	}

	function exportIssue(&$issue) {
		$doc = &XMLWriter::createDocument('issue', '/native.dtd');
		$issueNode = &$this->generateIssueDom(&$doc, &$issue);
		XMLWriter::appendChild(&$doc, &$issueNode);

		header("Content-Type: application/xml");
		echo XMLWriter::getXML(&$doc);
	}

	function generateIssueDom(&$doc, &$issue) {
		$root = &XMLWriter::createElement(&$doc, 'issue');

		XMLWriter::setAttribute(&$root, 'published', $issue->getPublished()?'true':'false');
		XMLWriter::setAttribute(&$root, 'current', $issue->getCurrent()?'true':'false');
		XMLWriter::setAttribute(&$root, 'public_id', $issue->getPublicIssueId(), false);

		XMLWriter::createChildWithText(&$doc, &$root, 'title', $issue->getTitle());
		XMLWriter::createChildWithText(&$doc, &$root, 'description', $issue->getDescription(), false);
		XMLWriter::createChildWithText(&$doc, &$root, 'volume', $issue->getVolume(), false);
		XMLWriter::createChildWithText(&$doc, &$root, 'number', $issue->getNumber(), false);
		XMLWriter::createChildWithText(&$doc, &$root, 'year', $issue->getYear(), false);

		if ($issue->getShowCoverPage()) {
			$coverNode = &XMLWriter::createElement(&$doc, 'cover');
			XMLWriter::appendChild(&$root, &$coverNode);
			XMLWriter::createChildWithText(&$doc, &$coverNode, 'caption', $issue->getCoverPageDescription(), false);

			$coverFile = $issue->getFileName();
			if ($coverFile != '') {
				$imageNode = &XMLWriter::createElement(&$doc, 'image');
				XMLWriter::appendChild(&$coverNode, &$imageNode);
				import('file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$journal = Request::getJournal();
				$coverPagePath = $publicFileManager->getJournalFilesPath($journal->getJournalId()) . '/';
				$coverPagePath .= $coverFile;
				$embedNode = &XMLWriter::createChildWithText(&$doc, &$imageNode, 'embed', base64_encode($publicFileManager->readFile($coverPagePath)));
				XMLWriter::setAttribute(&$embedNode, 'filename', $issue->getOriginalFileName());
				XMLWriter::setAttribute(&$embedNode, 'encoding', 'base64');
			}
		}

		XMLWriter::createChildWithText(&$doc, &$root, 'date_published', $this->formatDate($issue->getDatePublished()), false);

		if (XMLWriter::createChildWithText(&$doc, &$root, 'access_date', $this->formatDate($issue->getDatePublished()), false)==null) {
			// This may be an open access issue. Check and flag
			// as necessary.

			if ($issue->getAccessStatus()) {
				$accessNode = &XMLWriter::createElement(&$doc, 'open_access');
				XMLWriter::appendChild(&$root, &$accessNode);
			}
		}

		// FIXME: Section information *should* go here.

		return $root;
	}

	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-m-d', strtotime($date));
	}
}

?>
