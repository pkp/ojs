<?php

/**
 * GoogleScholarPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Google Scholar gateway plugin
 *
 * $Id$
 */

import('classes.plugins.GatewayPlugin');

import('xml.XMLWriter');

define('GOOGLE_SCHOLAR_ITEMS_PER_PAGE', 20);

class GoogleScholarPlugin extends GatewayPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
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
		return 'GoogleScholarPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.gateways.googleScholar.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.gateways.googleScholar.description');
	}

	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if (!$this->getEnabled()) return $verbs;
		$verbs[] = array(
			'settings', Locale::translate('plugins.gateways.googleScholar.settings')
		);
		return $verbs;
	}

	function manage($verb, $args) {
		if (parent::manage($verb, $args)) return true;
		if (!$this->getEnabled()) return false;
		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();
				$this->import('GoogleScholarSettingsForm');
				$form =& new GoogleScholarSettingsForm($this, $journal->getJournalId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, 'plugins');
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				break;
			case 'checkData':
				$errors = array();
				$pages = null;
				$publisherList =& $this->getPublisherList($pages, $errors);
				if ($publisherList) for ($i=1; $i<=$pages && empty($errors); $i++) {
					$this->getMetadataPage($i, $errors);
				}
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('errors', $errors);
				$templateMgr->display($this->getTemplatePath() . 'errors.tpl');
				break;
			default:
				return false;
		}
		return true;
	}

	function &getPublisherList(&$pages, &$errors) {
		import('xml.XMLWriter');
		$falseVar = false;
		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		$document =& XMLWriter::createDocument('publisher', 'publisher.dtd');
		$publisherNode =& XMLWriter::createElement($document, 'publisher');
		XMLWriter::appendChild($document, $publisherNode);

		// Publisher information
		$publisherName = $this->getSetting($journalId, 'publisher-name');
		if (empty($publisherName)) {
			array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noPublisherName'));
			return $falseVar;
		}
		XMLWriter::createChildWithText($document, $publisherNode, 'publisher-name', $publisherName, true);
		$publisherLocation = $this->getSetting($journalId, 'publisher-location');
		XMLWriter::createChildWithText($document, $publisherNode, 'publisher-location', $publisherLocation, false);
		$publisherResultName = $this->getSetting($journalId, 'publisher-result-name');
		XMLWriter::createChildWithText($document, $publisherNode, 'publisher-result-name', $publisherResultName, false);

		// Contact information
		$contactEmails = $this->getSetting($journal->getJournalId(), 'contact');
		if (is_array($contactEmails) && !empty($contactEmails)) {
			foreach ($contactEmails as $email) {
				XMLWriter::createChildWithText($document, $publisherNode, 'contact', $email, true);
			}
		} else {
			array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noContacts'));
			return $falseVar;
		}

		// Metadata files
		$metadataFilesNode =& XMLWriter::createElement($document, 'metadata-files');
		XMLWriter::appendChild($publisherNode, $metadataFilesNode);

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$count = $publishedArticleDao->getPublishedArticleCountByJournalId($journal->getJournalId());
		for ($i=1; ($i-1)*GOOGLE_SCHOLAR_ITEMS_PER_PAGE<$count; $i++) {
			$fileNode =& XMLWriter::createElement($document, 'file');
			XMLWriter::appendChild($metadataFilesNode, $fileNode);
			XMLWriter::createChildWithText(
				$document,
				$fileNode,
				'url',
				Request::url(null, null, null, array(
					'plugin',
					$this->getName(),
					$i
				)),
				true
			);
		}
		$pages = $i;
		return $document;
	}

	function &getMetadataPage($pageNum, &$errors) {
		import('xml.XMLWriter');
		import('db.DBResultRange');
		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();
		$falseVar = false;

		if ($pageNum < 1) return $falseVar;

		$rangeInfo =& new DBResultRange(GOOGLE_SCHOLAR_ITEMS_PER_PAGE, $pageNum);
		$document =& XMLWriter::createDocument('articles', 'articles.dtd');
		$articlesNode =& XMLWriter::createElement($document, 'articles');
		XMLWriter::appendChild($document, $articlesNode);

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles =& $publishedArticleDao->getPublishedArticlesByJournalId($journalId, $rangeInfo);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueCache = array();

		while ($publishedArticle =& $publishedArticles->next()) {
			$articleNode =& XMLWriter::createElement($document, 'article');
			XMLWriter::appendChild($articlesNode, $articleNode);

			$frontNode =& XMLWriter::createElement($document, 'front');
			XMLWriter::appendChild($articleNode, $frontNode);

			$journalMetaNode =& XMLWriter::createElement($document, 'journal-meta');
			XMLWriter::appendChild($frontNode, $journalMetaNode);

			// Journal Metadata
			$journal =& Request::getJournal();
			XMLWriter::createChildWithText($document, $journalMetaNode, 'journal-title', $journal->getTitle(), true);
			XMLWriter::createChildWithText($document, $journalMetaNode, 'abbrev-journal-title', $journal->getSetting('journalInitials'), false);

			$issn = $journal->getSetting('issn');
			if (empty($issn)) {
				array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noIssn'));
				return $falseVar;
			}
			XMLWriter::createChildWithText($document, $journalMetaNode, 'issn', $journal->getSetting('issn'), false);

			$publisherNode =& XMLWriter::createElement($document, 'publisher');
			$publisherName = $this->getSetting($journalId, 'publisher-name');
			if (empty($publisherName)) {
				array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noPublisherName'));
				return $falseVar;
			}
			XMLWriter::createChildWithText($document, $publisherNode, 'publisher-name', $publisherName, true);
			XMLWriter::appendChild($journalMetaNode, $publisherNode);
			
			$articleMetaNode =& XMLWriter::createElement($document, 'article-meta');
			XMLWriter::appendChild($frontNode, $articleMetaNode);

			// Article Metadata
			$titleGroupNode =& XMLWriter::createElement($document, 'title-group');
			XMLWriter::appendChild($articleMetaNode, $titleGroupNode);
			XMLWriter::createChildWithText($document, $titleGroupNode, 'article-title', $publishedArticle->getTitle(), true);
			$altTitle = $publishedArticle->getTitleAlt1();
			if (empty($altTitle)) $altTitle = $publishedArticle->getTitleAlt2();
			XMLWriter::createChildWithText($document, $titleGroupNode, 'trans-title', $altTitle, false);

			$contribGroupNode =& XMLWriter::createElement($document, 'contrib-group');
			XMLWriter::appendChild($articleMetaNode, $contribGroupNode);
			foreach ($publishedArticle->getAuthors() as $author) {
				$contribNode =& XMLWriter::createElement($document, 'contrib');
				XMLWriter::appendChild($contribGroupNode, $contribNode);
				XMLWriter::setAttribute($contribNode, 'contrib-type', 'author');
				$nameNode =& XMLWriter::createElement($document, 'name');
				XMLWriter::appendChild($contribNode, $nameNode);
				XMLWriter::createChildWithText($document, $nameNode, 'surname', $author->getLastName(), true);

				// Given names in the form: FirstName MiddleName, where MiddleName is optional
				$name = $author->getFirstName();
				if (($middleName = $author->getMiddleName()) != '') $name .= " $middleName";

				XMLWriter::createChildWithText($document, $nameNode, 'given-names', $name, true);

			}

			$dateParts = getdate(strtotime($publishedArticle->getDatePublished()));
			$pubDateNode = XMLWriter::createElement($document, 'pub-date');
			XMLWriter::appendChild($articleMetaNode, $pubDateNode);
			XMLWriter::createChildWithText($document, $pubDateNode, 'day', $dateParts['mday']);
			XMLWriter::createChildWithText($document, $pubDateNode, 'month', $dateParts['mon']);
			XMLWriter::createChildWithText($document, $pubDateNode, 'year', $dateParts['year']);

			$issueId = $publishedArticle->getIssueId();
			if (!isset($issueCache[$issueId])) {
				$issueCache[$issueId] =& $issueDao->getIssueById($issueId);
			}
			$issue =& $issueCache[$issueId];
			XMLWriter::createChildWithText($document, $articleMetaNode, 'volume', $issue->getVolume());
			XMLWriter::createChildWithText($document, $articleMetaNode, 'issue', $issue->getNumber());

			$canonicalUriNode =& XMLWriter::createElement($document, 'self-uri');
			XMLWriter::setAttribute($canonicalUriNode, 'xlink:href', Request::url(null, 'article', 'view', array($publishedArticle->getArticleId())));
			XMLWriter::appendChild($articleMetaNode, $canonicalUriNode);
			foreach ($publishedArticle->getGalleys() as $galley) {
				$galleyUriNode =& XMLWriter::createElement($document, 'self-uri');
				XMLWriter::setAttribute($galleyUriNode, 'xlink:href', Request::url(null, 'article', 'view', array($publishedArticle->getArticleId(), $galley->getGalleyId())));
				XMLWriter::appendChild($articleMetaNode, $galleyUriNode);
			}
		}

		return $document;
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		if (!$this->getEnabled()) {
			return false;
		}

		if (empty($args)) {
			$errors = array();
			$pages = null;
			$publisherList =& $this->getPublisherList($pages, $errors);
			if ($publisherList) {
				header('Content-Type: application/xml');
				XMLWriter::printXML($publisherList);
				return true;
			}
		} else {
			$errors = array();
			$pageNum = (int) array_shift($args);
			$metadataPage =& $this->getMetadataPage($pageNum, $errors);
			if ($metadataPage) {
				header('Content-Type: application/xml');
				XMLWriter::printXML($metadataPage);
				return true;
			}
		}
		// Failure.
		header("HTTP/1.0 500 Internal Server Error");
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', 'plugins.gateways.googleScholar.errors.errorMessage');
		$templateMgr->display('common/message.tpl');
		exit;
	}
}

?>
