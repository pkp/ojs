<?php

/**
 * GoogleScholarPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Google Scholar gateway plugin
 *
 * $Id$
 */

import('classes.plugins.GatewayPlugin');

import('xml.XMLCustomWriter');

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
		import('xml.XMLCustomWriter');
		$falseVar = false;
		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();

		$document =& XMLCustomWriter::createDocument('publisher', 'publisher.dtd');
		$publisherNode =& XMLCustomWriter::createElement($document, 'publisher');
		XMLCustomWriter::appendChild($document, $publisherNode);

		// Publisher information
		$publisherName = $this->getSetting($journalId, 'publisher-name');
		if (empty($publisherName)) {
			array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noPublisherName'));
			return $falseVar;
		}
		XMLCustomWriter::createChildWithText($document, $publisherNode, 'publisher-name', $publisherName, true);
		$publisherLocation = $this->getSetting($journalId, 'publisher-location');
		XMLCustomWriter::createChildWithText($document, $publisherNode, 'publisher-location', $publisherLocation, false);
		$publisherResultName = $this->getSetting($journalId, 'publisher-result-name');
		XMLCustomWriter::createChildWithText($document, $publisherNode, 'publisher-result-name', $publisherResultName, false);

		// Contact information
		$contactEmails = $this->getSetting($journal->getJournalId(), 'contact');
		if (is_array($contactEmails) && !empty($contactEmails)) {
			foreach ($contactEmails as $email) {
				XMLCustomWriter::createChildWithText($document, $publisherNode, 'contact', $email, true);
			}
		} else {
			array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noContacts'));
			return $falseVar;
		}

		// Metadata files
		$metadataFilesNode =& XMLCustomWriter::createElement($document, 'metadata-files');
		XMLCustomWriter::appendChild($publisherNode, $metadataFilesNode);

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$count = $publishedArticleDao->getPublishedArticleCountByJournalId($journal->getJournalId());
		for ($i=1; ($i-1)*GOOGLE_SCHOLAR_ITEMS_PER_PAGE<$count; $i++) {
			$fileNode =& XMLCustomWriter::createElement($document, 'file');
			XMLCustomWriter::appendChild($metadataFilesNode, $fileNode);
			XMLCustomWriter::createChildWithText(
				$document,
				$fileNode,
				'url',
				Request::url(null, null, null, array(
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
		import('xml.XMLCustomWriter');
		import('db.DBResultRange');
		$journal =& Request::getJournal();
		$journalId = $journal->getJournalId();
		$falseVar = false;

		if ($pageNum < 1) return $falseVar;

		$rangeInfo =& new DBResultRange(GOOGLE_SCHOLAR_ITEMS_PER_PAGE, $pageNum);
		$document =& XMLCustomWriter::createDocument('articles', 'articles.dtd');
		$articlesNode =& XMLCustomWriter::createElement($document, 'articles');
		XMLCustomWriter::appendChild($document, $articlesNode);

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles =& $publishedArticleDao->getPublishedArticlesByJournalId($journalId, $rangeInfo);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueCache = array();

		while ($publishedArticle =& $publishedArticles->next()) {
			$articleNode =& XMLCustomWriter::createElement($document, 'article');
			XMLCustomWriter::appendChild($articlesNode, $articleNode);

			$frontNode =& XMLCustomWriter::createElement($document, 'front');
			XMLCustomWriter::appendChild($articleNode, $frontNode);

			$journalMetaNode =& XMLCustomWriter::createElement($document, 'journal-meta');
			XMLCustomWriter::appendChild($frontNode, $journalMetaNode);

			// Journal Metadata
			$journal =& Request::getJournal();
			XMLCustomWriter::createChildWithText($document, $journalMetaNode, 'journal-title', $journal->getTitle(), true);
			XMLCustomWriter::createChildWithText($document, $journalMetaNode, 'abbrev-journal-title', $journal->getSetting('journalInitials'), false);

			$issn = $journal->getSetting('onlineIssn');
			if (empty($issn)) {
				array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noIssn'));
				return $falseVar;
			}
			XMLCustomWriter::createChildWithText($document, $journalMetaNode, 'issn', $issn, false);

			$publisherNode =& XMLCustomWriter::createElement($document, 'publisher');
			$publisherName = $this->getSetting($journalId, 'publisher-name');
			if (empty($publisherName)) {
				array_push($errors, Locale::translate('plugins.gateways.googleScholar.errors.noPublisherName'));
				return $falseVar;
			}
			XMLCustomWriter::createChildWithText($document, $publisherNode, 'publisher-name', $publisherName, true);
			XMLCustomWriter::appendChild($journalMetaNode, $publisherNode);
			
			$articleMetaNode =& XMLCustomWriter::createElement($document, 'article-meta');
			XMLCustomWriter::appendChild($frontNode, $articleMetaNode);

			// Article Metadata
			$titleGroupNode =& XMLCustomWriter::createElement($document, 'title-group');
			XMLCustomWriter::appendChild($articleMetaNode, $titleGroupNode);
			XMLCustomWriter::createChildWithText($document, $titleGroupNode, 'article-title', $publishedArticle->getTitle(), true);
			$altTitle = $publishedArticle->getTitleAlt1();
			if (empty($altTitle)) $altTitle = $publishedArticle->getTitleAlt2();
			XMLCustomWriter::createChildWithText($document, $titleGroupNode, 'trans-title', $altTitle, false);

			$contribGroupNode =& XMLCustomWriter::createElement($document, 'contrib-group');
			XMLCustomWriter::appendChild($articleMetaNode, $contribGroupNode);
			foreach ($publishedArticle->getAuthors() as $author) {
				$contribNode =& XMLCustomWriter::createElement($document, 'contrib');
				XMLCustomWriter::appendChild($contribGroupNode, $contribNode);
				XMLCustomWriter::setAttribute($contribNode, 'contrib-type', 'author');
				$nameNode =& XMLCustomWriter::createElement($document, 'name');
				XMLCustomWriter::appendChild($contribNode, $nameNode);
				XMLCustomWriter::createChildWithText($document, $nameNode, 'surname', $author->getLastName(), true);

				// Given names in the form: FirstName MiddleName, where MiddleName is optional
				$name = $author->getFirstName();
				if (($middleName = $author->getMiddleName()) != '') $name .= " $middleName";

				XMLCustomWriter::createChildWithText($document, $nameNode, 'given-names', $name, true);

			}

			$dateParts = getdate(strtotime($publishedArticle->getDatePublished()));
			$pubDateNode =& XMLCustomWriter::createElement($document, 'pub-date');
			XMLCustomWriter::appendChild($articleMetaNode, $pubDateNode);
			XMLCustomWriter::createChildWithText($document, $pubDateNode, 'day', $dateParts['mday']);
			XMLCustomWriter::createChildWithText($document, $pubDateNode, 'month', $dateParts['mon']);
			XMLCustomWriter::createChildWithText($document, $pubDateNode, 'year', $dateParts['year']);

			$issueId = $publishedArticle->getIssueId();
			if (!isset($issueCache[$issueId])) {
				$issueCache[$issueId] =& $issueDao->getIssueById($issueId);
			}
			$issue =& $issueCache[$issueId];
			XMLCustomWriter::createChildWithText($document, $articleMetaNode, 'volume', $issue->getVolume());
			XMLCustomWriter::createChildWithText($document, $articleMetaNode, 'issue', $issue->getNumber());

			$canonicalUriNode =& XMLCustomWriter::createElement($document, 'self-uri');
			XMLCustomWriter::setAttribute($canonicalUriNode, 'xlink:href', Request::url(null, 'article', 'viewArticle', array($publishedArticle->getArticleId())));
			XMLCustomWriter::appendChild($articleMetaNode, $canonicalUriNode);
			foreach ($publishedArticle->getGalleys() as $galley) {
				$galleyUriNode =& XMLCustomWriter::createElement($document, 'self-uri');
				if ($galley->isHTMLGalley()) XMLCustomWriter::setAttribute($galleyUriNode, 'xlink:href', Request::url(null, 'article', 'viewArticle', array($publishedArticle->getArticleId(), $galley->getGalleyId())));
				else XMLCustomWriter::setAttribute($galleyUriNode, 'xlink:href', Request::url(null, 'article', 'viewFile', array($publishedArticle->getArticleId(), $galley->getGalleyId())));
				XMLCustomWriter::appendChild($articleMetaNode, $galleyUriNode);
			}
			unset($issue);
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
				XMLCustomWriter::printXML($publisherList);
				return true;
			}
		} else {
			$errors = array();
			$pageNum = (int) array_shift($args);
			$metadataPage =& $this->getMetadataPage($pageNum, $errors);
			if ($metadataPage) {
				header('Content-Type: application/xml');
				XMLCustomWriter::printXML($metadataPage);
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
