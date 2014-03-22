<?php

/**
 * @file plugins/importexport/colobokPlugin.inc.php
 *
 * Copyright (c) 2003-2013 Artem Gusarenko
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class colobokPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief colobokPlugin import/export plugin
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

import('classes.plugins.ImportExportPlugin');

define('COLOBOK_XSD_URL', 'http://www.doaj.org/schemas/colobokArticles.xsd');

class colobokPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
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
		return 'colobokPlugin';
	}

	/**
	 * Get the display name for this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.importexport.colobok.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.colobok.description');
	}

	/**
	 * Display the plugin
	 * @param $args array
	 */
	function display(&$args, $request) {
		//$templateMgr =& TemplateManager::getManager();
		//parent::display($args, $request);
		//$journal =& Request::getJournal();
		
		/////////
		$templateMgr =& TemplateManager::getManager();
		parent::display($args, $request);
		//$issueDao =& DAORegistry::getDAO('IssueDAO');
		$journal =& $request->getJournal();
		//$journal =& Request::getJournal();
		switch (array_shift($args)) {
			/*case 'exportIssues':
				$issueIds = $request->getUserVar('issueId');
				if (!isset($issueIds)) $issueIds = array();
				$issues = array();
				foreach ($issueIds as $issueId) {
					$issue =& $issueDao->getIssueById($issueId, $journal->getId());
					if (!$issue) $request->redirect();
					$issues[] =& $issue;
					unset($issue);
				}
				$this->exportIssues($journal, $issues);
				break;*/
			case 'exportIssue':
				$issueId = array_shift($args);
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issue =& $issueDao->getIssueById($issueId, $journal->getId());
				if (!$issue) $request->redirect();
				$this->exportJournal($journal, $issue);
				break;
			/*case 'exportArticle':
				$articleIds = array(array_shift($args));
				$result = array_shift(ArticleSearch::formatResults($articleIds));
				$this->exportArticle($journal, $result['issue'], $result['section'], $result['publishedArticle']);
				break;
			case 'exportArticles':
				$articleIds = $request->getUserVar('articleId');
				if (!isset($articleIds)) $articleIds = array();
				$results =& ArticleSearch::formatResults($articleIds);
				$this->exportArticles($results);
				break;*/
			case 'issues':
				// Display a list of issues for export
				$this->setBreadcrumbs(array(), true);
				AppLocale::requireComponents(LOCALE_COMPONENT_OJS_EDITOR);
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getIssues($journal->getId(), Handler::getRangeInfo('issues'));
		
				$templateMgr->assign_by_ref('issues', $issues);
				$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
				break;
			/*case 'articles':
				// Display a list of articles for export
				$this->setBreadcrumbs(array(), true);
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getId(), false);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('lib.pkp.classes.core.VirtualArrayIterator');
				$iterator = new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
				break;*/
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
		/////////
		
		/*switch (array_shift($args)) {
			case 'export':
				// export an xml file with the journal's information
				$this->exportJournal($journal);
				break;
			case 'contact':
				// present a form autofilled with journal information to send to the DOAJ representative
				$this->contact($journal);
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}	*/
	}

	/**
	 * Export a journal's content
	 * @param $journal object
	 * @param $outputFile string
	 */
	function exportJournal(&$journal, &$issue, $outputFile = null) {
		$this->import('colobokExportDom');
		$doc =& XMLCustomWriter::createDocument();
		$doc->formatOutput = true;
		$journalNode =& colobokExportDom::generateJournalDom($doc, $journal, $issue);
		$journalNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$journalNode->setAttribute('xsi:noNamespaceSchemaLocation', COLOBOK_XSD_URL);
		XMLCustomWriter::appendChild($doc, $journalNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'wb'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"issue" . $journal->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	/**
	 * Auto-fill the DOAJ form.
	 * @param $journal object
	 */
	/*function contact(&$journal, $send = false) {
		$user =& Request::getUser();

		$issn = $journal->getSetting('printIssn');

		$paramArray = array(
			'name' => $user->getFullName(),
			'email' => $user->getEmail(),
			'title' => $journal->getLocalizedTitle(),
			'description' => String::html2text($journal->getLocalizedSetting('focusScopeDesc')),
			'url' => $journal->getUrl(),
			'charging' => $journal->getSetting('submissionFee') > 0 ? 'Y' : 'N',
			'issn' => $issn,
			'eissn' => $journal->getSetting('onlineIssn'),
			'pub' => $journal->getSetting('publisherInstitution'),
			'language' => AppLocale::getLocale(),
			'keywords' => $journal->getLocalizedSetting('searchKeywords'),
			'contact_person' => $journal->getSetting('contactName'),
			'contact_email' => $journal->getSetting('contactEmail')
		);
		$url = 'http://www.doaj.org/doaj?func=suggest&owner=1';
		foreach ($paramArray as $name => $value) {
			$url .= '&' . urlencode($name) . '=' . urlencode($value);
		}
		Request::redirectUrl($url);
	}*/
}

?>
