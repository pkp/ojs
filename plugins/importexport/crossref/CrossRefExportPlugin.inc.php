<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportPlugin
 * @ingroup plugins_importexport_crossref
 *
 * @brief CrossRef/MEDLINE XML metadata export plugin
 */

import('classes.plugins.ImportExportPlugin');

class CrossRefExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'CrossRefExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.crossref.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.crossref.description');
	}

	function display(&$args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		parent::display($args, $request);

		$issueDao = DAORegistry::getDAO('IssueDAO');

		$journal = $request->getJournal();

		switch (array_shift($args)) {
			case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue = $issueDao->getById($issueId);
					if (!$issue) $request->redirect();
					$issues[] = $issue;
				}
				$this->exportIssues($journal, $issues);
				break;
			case 'exportIssue':
				$issueId = array_shift($args);
				$issue = $issueDao->getById($issueId);
				if (!$issue) $request->redirect();
				$issues = array($issue);
				$this->exportIssues($journal, $issues);
				break;
			case 'exportArticle':
				$articleIds = array(array_shift($args));
				$articleSearch = new ArticleSearch();
				$result = $articleSearch->formatResults($articleIds);
				$this->exportArticles($journal, $result);
				break;
			case 'exportArticles':
				$articleIds = $request->getUserVar('articleId');
				if (!isset($articleIds)) $articleIds = array();
				$articleSearch = new ArticleSearch();
				$results = $articleSearch->formatResults($articleIds);
				$this->exportArticles($journal, $results);
				break;
			case 'issues':
				// Display a list of issues for export
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issues = $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo($this->getRequest(), 'issues'));

				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			case 'articles':
				// Display a list of articles for export
				$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo($this->getRequest, 'articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsByJournal($journal->getId(), false);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('lib.pkp.classes.core.VirtualArrayIterator');
				$articleSearch = new ArticleSearch();
				$iterator = new VirtualArrayIterator($articleSearch->formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
				break;
			default:
				$doiPrefix = null;
				$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
				if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
					$doiPrefix = $pubIdPlugins['DOIPubIdPlugin']->getSetting($journal->getId(), 'doiPrefix');
				}
				$templateMgr->assign('doiPrefix', $doiPrefix);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportArticles(&$journal, &$results, $outputFile = null) {
		$this->import('CrossRefExportDom');

		$doc =& CrossRefExportDom::generateCrossRefDom();
		$doiBatchNode =& CrossRefExportDom::generateDoiBatchDom($doc);

		// Create Head Node and all parts inside it
		$head =& CrossRefExportDom::generateHeadDom($doc, $journal);

		// attach it to the root node
		XMLCustomWriter::appendChild($doiBatchNode, $head);

		// the body node contains everything
		$bodyNode =& XMLCustomWriter::createElement($doc, 'body');
		XMLCustomWriter::appendChild($doiBatchNode, $bodyNode);

		// now cycle through everything we want to submit in this batch
		foreach ($results as $result) {
			$journal =& $result['journal'];
			$issue =& $result['issue'];
			$section =& $result['section'];
			$article =& $result['publishedArticle'];

			// Create the metadata node
			// this does not need to be repeated for every article
			// but its allowed to be and its simpler to do so
			$journalNode =& XMLCustomWriter::createElement($doc, 'journal');
			$journalMetadataNode =& CrossRefExportDom::generateJournalMetadataDom($doc, $journal);
			XMLCustomWriter::appendChild($journalNode, $journalMetadataNode);

			// Create the journal_issue node
			$journalIssueNode =& CrossRefExportDom::generateJournalIssueDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($journalNode, $journalIssueNode);

			// Create the article
			$journalArticleNode =& CrossRefExportDom::generateJournalArticleDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($journalNode, $journalArticleNode);

			XMLCustomWriter::appendChild($bodyNode, $journalNode);
		}


		// dump out the results
		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"crossref.xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	function exportIssues(&$journal, &$issues, $outputFile = null) {
		$this->import('CrossRefExportDom');

		$doc =& CrossRefExportDom::generateCrossRefDom();
		$doiBatchNode =& CrossRefExportDom::generateDoiBatchDom($doc);

		$request =& $this->getRequest();

		// Create Head Node and all parts inside it
		$head =& CrossRefExportDom::generateHeadDom($doc, $journal);

		// attach it to the root node
		XMLCustomWriter::appendChild($doiBatchNode, $head);

		$bodyNode =& XMLCustomWriter::createElement($doc, 'body');
		XMLCustomWriter::appendChild($doiBatchNode, $bodyNode);

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		foreach ($issues as $issue) {
			foreach ($sectionDao->getByIssueId($issue->getId()) as $section) {
				foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
					// Create the metadata node
					// this does not need to be repeated for every article
					// but its allowed to be and its simpler to do so
					$journalNode =& XMLCustomWriter::createElement($doc, 'journal');
					$journalMetadataNode =& CrossRefExportDom::generateJournalMetadataDom($doc, $journal);
					XMLCustomWriter::appendChild($journalNode, $journalMetadataNode);

					$journalIssueNode =& CrossRefExportDom::generateJournalIssueDom($doc, $journal, $issue, $section, $article);
					XMLCustomWriter::appendChild($journalNode, $journalIssueNode);

					// Article node
					$journalArticleNode =& CrossRefExportDom::generateJournalArticleDom($doc, $journal, $issue, $section, $article);
					XMLCustomWriter::appendChild($journalNode, $journalArticleNode);

					XMLCustomWriter::appendChild($bodyNode, $journalNode);
				}
			}
		}

		// dump out results
		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"crossref.xml\"");
			XMLCustomWriter::printXML($doc);
		}

		return true;
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
//		$command = array_shift($args);
		$xmlFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');

		$journal = $journalDao->getByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.crossref.cliError') . "\n";
				echo __('plugins.importexport.crossref.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($xmlFile != '') switch (array_shift($args)) {
			case 'articles':
				$articleSearch = new ArticleSearch();
				$results = $articleSearch->formatResults($args);
				if (!$this->exportArticles($journal, $results, $xmlFile)) {
					echo __('plugins.importexport.crossref.cliError') . "\n";
					echo __('plugins.importexport.crossref.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
			case 'issue':
				$issueId = array_shift($args);
				$issue = $issueDao->getByBestId($issueId, $journal->getId());
				if ($issue == null) {
					echo __('plugins.importexport.crossref.cliError') . "\n";
					echo __('plugins.importexport.crossref.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
					return;
				}
				$issues = array($issue);
				if (!$this->exportIssues($journal, $issues, $xmlFile)) {
					echo __('plugins.importexport.crossref.cliError') . "\n";
					echo __('plugins.importexport.crossref.export.error.couldNotWrite', array('fileName' => $xmlFile)) . "\n\n";
				}
				return;
		}
		$this->usage($scriptName);

	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.crossref.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
