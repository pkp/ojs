<?php

/**
 * @file plugins/importexport/crossref/classes/CrossRefExportDom.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportDom
 * @ingroup plugins_importexport_crossref_classes
 *
 * @brief CrossRef XML export format implementation.
 */


if (!class_exists('DOIExportDom')) { // Bug #7848
	import('plugins.importexport.crossref.classes.DOIExportDom');
}

// XML attributes
define('CROSSREF_XMLNS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('CROSSREF_XMLNS' , 'http://www.crossref.org/schema/4.3.3');
define('CROSSREF_VERSION' , '4.3.3');
define('CROSSREF_XSI_SCHEMAVERSION' , '4.3.3');
define('CROSSREF_XSI_SCHEMALOCATION' , 'http://www.crossref.org/schema/4.3.3 http://www.crossref.org/schema/deposit/crossref4.3.3.xsd');

class CrossRefExportDom extends DOIExportDom {

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $request Request
	 * @param $plugin DOIExportPlugin
	 * @param $journal Journal
	 * @param $objectCache PubObjectCache
	 */
	function CrossRefExportDom(&$request, &$plugin, &$journal, &$objectCache) {
		// Configure the DOM.
		parent::DOIExportDom($request, $plugin, $journal, $objectCache);
	}


	//
	// Public methods
	//
	/**
	 * @see DOIExportDom::generate()
	 */
	function &generate(&$objects) {
		$journal =& $this->getJournal();

		// Create the XML document and its root element.
		$doc =& $this->getDoc();
		$rootElement =& $this->rootElement();
		XMLCustomWriter::appendChild($doc, $rootElement);

		// Create Head Node and all parts inside it
		$head =& $this->_generateHeadDom($doc, $journal);
		// attach it to the root node
		XMLCustomWriter::appendChild($rootElement, $head);

		// the body node contains everything
		$bodyNode =& XMLCustomWriter::createElement($doc, 'body');
		XMLCustomWriter::appendChild($rootElement, $bodyNode);

		foreach($objects as $object) {
			// Retrieve required publication objects.
			$pubObjects =& $this->retrievePublicationObjects($object);
			extract($pubObjects);
			$issue =& $pubObjects['issue'];
			if (is_a($object, 'Issue')) {
				foreach ($pubObjects['articlesByIssue'] as $article) {
					if ($article->getPubId('doi')) {
						$this->_appendArticleXML($doc, $journal, $issue, $article, $bodyNode);
					}
				}
			} else {
				$article =& $pubObjects['article'];
				if ($article->getPubId('doi')) {
					$this->_appendArticleXML($doc, $journal, $issue, $article, $bodyNode);
				}
			}
		}

		return $doc;
	}

	//
	// Implementation of template methods from DOIExportDom
	//
	/**
	 * @see DOIExportDom::getRootElementName()
	 */
	function getRootElementName() {
		return 'doi_batch';
	}

	/**
	 * @see DOIExportDom::getNamespace()
	 */
	function getNamespace() {
		return CROSSREF_XMLNS;
	}

	/**
	 * @see DOIExportDom::getXmlSchemaVersionn()
	 */
	function getXmlSchemaVersion() {
		return CROSSREF_XSI_SCHEMAVERSION;
	}

	/**
	 * @see DOIExportDom::getXmlSchemaLocation()
	 */
	function getXmlSchemaLocation() {
		return CROSSREF_XSI_SCHEMALOCATION;
	}

	/**
	 * @see DOIExportDom::retrievePublicationObjects()
	 */
	function &retrievePublicationObjects(&$object) {
		// Initialize local variables.
		$nullVar = null;
		$journal =& $this->getJournal();
		$cache =& $this->getCache();

		// Retrieve basic OJS objects.
		$publicationObjects = parent::retrievePublicationObjects($object);

		// Retrieve additional related objects.
		// For articles: no additional objects needed for CrossRef:
		// galleys are not considered and
		// supp files will be retrieved when crating the XML
		// Note: article issue is already retrieved by the parent method
		if (is_a($object, 'PublishedArticle')) {
			$article =& $publicationObjects['article'];
		}

		// For issues: Retrieve all articles of the issue:
		if (is_a($object, 'Issue')) {
			// Articles by issue.
			assert(isset($publicationObjects['issue']));
			$issue =& $publicationObjects['issue'];
			$publicationObjects['articlesByIssue'] =& $this->retrieveArticlesByIssue($issue);
		}

		return $publicationObjects;
	}


	//
	// Private helper methods
	//
	/**
	 * Generate the <head> tag that accompanies each submission
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @return XMLNode
	 */
	function &_generateHeadDom(&$doc, &$journal) {
		$head =& XMLCustomWriter::createElement($doc, 'head');

		// DOI batch ID is a simple tracking ID: initials + timestamp
		XMLCustomWriter::createChildWithText($doc, $head, 'doi_batch_id', $journal->getLocalizedSetting('initials') . '_' . time());
		XMLCustomWriter::createChildWithText($doc, $head, 'timestamp', time());

		$journalId = $journal->getId();

		/* Depositor defaults to the Journal's technical Contact */
		$plugin = $this->_plugin;
		$depositorName = $plugin->getSetting($journalId, 'depositorName');
		if (empty($depositorName)) {
			$depositorName = $journal->getSetting('supportName');
		}
		$depositorEmail = $plugin->getSetting($journalId, 'depositorEmail');
		if (empty($depositorEmail)) {
			$depositorEmail = $journal->getSetting('supportEmail');
		}
		$depositorNode =& $this->_generateDepositorDom($doc, $depositorName, $depositorEmail);
		XMLCustomWriter::appendChild($head, $depositorNode);

		/* The registrant is assumed to be the Publishing institution */
		$publisherInstitution = $journal->getSetting('publisherInstitution');
		XMLCustomWriter::createChildWithText($doc, $head, 'registrant', $publisherInstitution);

		return $head;
	}

	/**
	 * Generate depositor node
	 * @param $doc XMLNode
	 * @param $name string
	 * @param $email string
	 * @return XMLNode
	 */
	function &_generateDepositorDom(&$doc, $name, $email) {
		$depositor =& XMLCustomWriter::createElement($doc, 'depositor');
		XMLCustomWriter::createChildWithText($doc, $depositor, 'name', $name);
		XMLCustomWriter::createChildWithText($doc, $depositor, 'email_address', $email);

		return $depositor;
	}

	/**
	 * Generate and append the XML per article
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $article Article
	 * @param $bodyNode XMLNode
	 */
	function _appendArticleXML(&$doc, &$journal, &$issue, &$article, &$bodyNode) {
		$sectionId = $article->getSectionId();
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($sectionId);

		// Create the journal node
		$journalNode =& XMLCustomWriter::createElement($doc, 'journal');
		$journalMetadataNode =& $this->_generateJournalMetadataDom($doc, $journal);
		XMLCustomWriter::appendChild($journalNode, $journalMetadataNode);

		// Create the journal_issue node
		$journalIssueNode =& $this->_generateJournalIssueDom($doc, $journal, $issue, $section, $article);
		XMLCustomWriter::appendChild($journalNode, $journalIssueNode);

		// Create the article node
		$journalArticleNode =& $this->_generateJournalArticleDom($doc, $journal, $issue, $section, $article);
		XMLCustomWriter::appendChild($journalNode, $journalArticleNode);
		XMLCustomWriter::appendChild($bodyNode, $journalNode);
	}

	/**
	 * Generate metadata for journal - accompanies every article
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @return XMLNode
	 */
	function &_generateJournalMetadataDom(&$doc, &$journal) {
		$journalMetadataNode =& XMLCustomWriter::createElement($doc, 'journal_metadata');

		/* Full Title of Journal */
		$journalTitle = $journal->getLocalizedTitle();
		// Attempt a fall back, in case the localized name is not set.
		if ($journalTitle == '') {
			$journalTitle = $journal->getLocalizedSetting('abbreviation');
		}
		XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'full_title', $journalTitle);

		/* Abbreviated title - defaulting to initials if no abbreviation found */
		if ($journal->getLocalizedSetting('abbreviation') != '' ) {
			XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'abbrev_title', $journal->getLocalizedSetting('abbreviation'));
		}
		else {
			XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'abbrev_title', $journal->getLocalizedSetting('initials'));
		}

		/* Both ISSNs are permitted for CrossRef, so sending whichever one (or both) */
		if ( $ISSN = $journal->getSetting('onlineIssn') ) {
			$onlineISSN =& XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'issn', $ISSN);
			XMLCustomWriter::setAttribute($onlineISSN, 'media_type', 'electronic');
		}

		/* Both ISSNs are permitted for CrossRef so sending whichever one (or both) */
		if ( $ISSN = $journal->getSetting('printIssn') ) {
			$printISSN =& XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'issn', $ISSN);
			XMLCustomWriter::setAttribute($printISSN, 'media_type', 'print');
		}

		return $journalMetadataNode;
	}

	/**
	 * Generate journal issue tag to accompany every article
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $section Section
	 * @param $article Article
	 * @return XMLNode
	 */
	function &_generateJournalIssueDom(&$doc, &$journal, &$issue, &$section, &$article) {
		$journalIssueNode =& XMLCustomWriter::createElement($doc, 'journal_issue');

		if ($issue->getDatePublished()) {
			$publicationDateNode =& $this->_generatePublisherDateDom($doc, $issue->getDatePublished());
			XMLCustomWriter::appendChild($journalIssueNode, $publicationDateNode);
		}

		if ($issue->getVolume()){
			$journalVolumeNode =& XMLCustomWriter::createElement($doc, 'journal_volume');
			XMLCustomWriter::appendChild($journalIssueNode, $journalVolumeNode);
			XMLCustomWriter::createChildWithText($doc, $journalVolumeNode, 'volume', $issue->getVolume());
		}
		if ($issue->getNumber()) {
			XMLCustomWriter::createChildWithText($doc, $journalIssueNode, 'issue', $issue->getNumber());
		}

		if ($issue->getDatePublished() && $issue->getPubId('doi')) {
			$issueDoiNode =& $this->_generateDOIdataDom($doc, $issue->getPubId('doi'), Request::url(null, 'issue', 'view', $issue->getBestIssueId($journal)));
			XMLCustomWriter::appendChild($journalIssueNode, $issueDoiNode);
		}

		return $journalIssueNode;
	}

	/**
	 * Generate the journal_article node (the heart of the file).
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $section Section
	 * @param $article Article
	 * @return XMLNode
	 */
	function &_generateJournalArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {
		// Create the base node
		$journalArticleNode =& XMLCustomWriter::createElement($doc, 'journal_article');
		XMLCustomWriter::setAttribute($journalArticleNode, 'publication_type', 'full_text');
		XMLCustomWriter::setAttribute($journalArticleNode, 'metadata_distribution_opts', 'any');

		/* Titles */
		$titlesNode =& XMLCustomWriter::createElement($doc, 'titles');
		XMLCustomWriter::createChildWithText($doc, $titlesNode, 'title', $article->getLocalizedTitle());
		XMLCustomWriter::appendChild($journalArticleNode, $titlesNode);

		/* AuthorList */
		$contributorsNode =& XMLCustomWriter::createElement($doc, 'contributors');
		$isFirst = true;
		foreach ($article->getAuthors() as $author) {
			$authorNode =& $this->_generateAuthorDom($doc, $author, $isFirst);
			$isFirst = false;
			XMLCustomWriter::appendChild($contributorsNode, $authorNode);
		}
		XMLCustomWriter::appendChild($journalArticleNode, $contributorsNode);

		/* publication date of article */
		if ($article->getDatePublished()) {
			$publicationDateNode =& $this->_generatePublisherDateDom($doc, $article->getDatePublished());
			XMLCustomWriter::appendChild($journalArticleNode, $publicationDateNode);
		}

		/* publisher_item is the article pages */
		if ($article->getPages() != '') {
			$pageNode =& XMLCustomWriter::createElement($doc, 'pages');
			// extract the first page for the first_page element, store the remaining bits in otherPages,
			// after removing any preceding non-numerical characters.
			if (preg_match('/^[^\d]*(\d+)\D*(.*)$/', $article->getPages(), $matches)) {
				$firstPage = $matches[1];
				$otherPages = $matches[2];
				XMLCustomWriter::createChildWithText($doc, $pageNode, 'first_page', $firstPage);
				if ($otherPages != '') {
					XMLCustomWriter::createChildWithText($doc, $pageNode, 'other_pages', $otherPages);
				}
			}
			XMLCustomWriter::appendChild($journalArticleNode, $pageNode);
		}

		// DOI data node
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$DOIdataNode =& $this->_generateDOIdataDom($doc, $article->getPubId('doi'), Request::url(null, 'article', 'view', $article->getBestArticleId()), $articleGalleyDao->getGalleysByArticle($article->getId()));

		XMLCustomWriter::appendChild($journalArticleNode, $DOIdataNode);

		/* Component list (supplementary files) */
		$componentListNode =& $this->_generateComponentListDom($doc, $journal, $article);
		if ($componentListNode) {
			XMLCustomWriter::appendChild($journalArticleNode, $componentListNode);
		}

		return $journalArticleNode;
	}

	/**
	 * Generate the component_list node (supplementary files).
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $section Section
	 * @param $article Article
	 * @return XMLNode
	 */
	function &_generateComponentListDom(&$doc, &$journal, &$article) {
		$suppFiles =& $article->getSuppFiles();
		$createComponentList = false;
		foreach ($suppFiles as $suppFile) {
			if ($suppFile->getPubId('doi')) {
				$createComponentList = true;
				break;
			}
		}
		if ($createComponentList) {
			// Create the base node
			$componentListNode =& XMLCustomWriter::createElement($doc, 'component_list');

			// Run through supp files and add component nodes.
			foreach($suppFiles as $suppFile) {
				if ($suppFile->getPubId('doi')) {

					$componentNode =& XMLCustomWriter::createElement($doc, 'component');
					XMLCustomWriter::setAttribute($componentNode, 'parent_relation', 'isPartOf');

					/* Titles */
					$suppFileTitle = $suppFile->getSuppFileTitle();
					if (!empty($suppFileTitle)) {
						$titlesNode =& XMLCustomWriter::createElement($doc, 'titles');
						XMLCustomWriter::createChildWithText($doc, $titlesNode, 'title', $suppFileTitle);
						XMLCustomWriter::appendChild($componentNode, $titlesNode);
					}

					// DOI data node
					$suppFileUrl = Request::url(
						null, 'article', 'downloadSuppFile',
						array($article->getId(), $suppFile->getBestSuppFileId($journal))
					);
					$suppFileDoiNode =& $this->_generateDOIdataDom($doc, $suppFile->getPubId('doi'), $suppFileUrl);
					XMLCustomWriter::appendChild($componentNode, $suppFileDoiNode);
				}

				XMLCustomWriter::appendChild($componentListNode, $componentNode);
				unset($componentNode);
			}
		}

		return $componentListNode;
	}

	/**
	 * Generate doi_data element - this is what assigns the DOI
	 * @param $doc XMLNode
	 * @param $DOI string
	 * @param $url string
	 * @param $galleys array
	 */
	function &_generateDOIdataDom(&$doc, $DOI, $url, $galleys = null) {
		$request = Application::getRequest();
		$journal = $request->getJournal();
		$DOIdataNode =& XMLCustomWriter::createElement($doc, 'doi_data');
		XMLCustomWriter::createChildWithText($doc, $DOIdataNode, 'doi', $DOI);
		XMLCustomWriter::createChildWithText($doc, $DOIdataNode, 'resource', $url);

		/* article galleys */
		if ($galleys) {
			$collectionNode = XMLCustomWriter::createElement($doc, 'collection');
			XMLCustomWriter::setAttribute($collectionNode, 'property', 'text-mining');
			XMLCustomWriter::appendChild($DOIdataNode, $collectionNode);
			foreach ($galleys as $galley) {
				$itemNode = XMLCustomWriter::createElement($doc, 'item');
				XMLCustomWriter::appendChild($collectionNode, $itemNode);
				$resourceNode = XMLCustomWriter::createElement($doc, 'resource');
				XMLCustomWriter::appendChild($itemNode, $resourceNode);
				XMLCustomWriter::setAttribute($resourceNode, 'mime_type', $galley->getFileType());
				$urlNode = XMLCustomWriter::createTextNode($doc, $request->url(null, 'article', 'viewFile', array($galley->getArticleId(), $galley->getBestGalleyId($journal))));
				XMLCustomWriter::appendChild($resourceNode, $urlNode);
			}
		}

		return $DOIdataNode;
	}


	/**
	 * Generate author node
	 * @param $doc XMLNode
	 * @param $author Author
	 * @return XMLNode
	 */
	function &_generateAuthorDom(&$doc, &$author, $isFirst = false) {
		$authorNode =& XMLCustomWriter::createElement($doc, 'person_name');
		XMLCustomWriter::setAttribute($authorNode, 'contributor_role', 'author');

		/* there should only be 1 primary contact per article */
		if ($isFirst) {
			XMLCustomWriter::setAttribute($authorNode, 'sequence', 'first');
		} else {
			XMLCustomWriter::setAttribute($authorNode, 'sequence', 'additional');
		}

		XMLCustomWriter::createChildWithText($doc, $authorNode, 'given_name', ucfirst($author->getFirstName()).(($author->getMiddleName())?' '.ucfirst($author->getMiddleName()):''));
		XMLCustomWriter::createChildWithText($doc, $authorNode, 'surname', ucfirst($author->getLastName()));
		if ($author->getData('orcid')) {
			XMLCustomWriter::createChildWithText($doc, $authorNode, 'ORCID', $author->getData('orcid'));
		}

		return $authorNode;
	}

	/**
	 * Generate publisher date - order matters
	 * @param $doc XMLNode
	 * @param $pubdate string
	 * @return XMLNode
	 */
	function &_generatePublisherDateDom(&$doc, $pubdate) {
		$publicationDateNode =& XMLCustomWriter::createElement($doc, 'publication_date');
		XMLCustomWriter::setAttribute($publicationDateNode, 'media_type', 'online');

		$parsedPubdate = strtotime($pubdate);
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'month', date('m', $parsedPubdate), false);
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'day', date('d', $parsedPubdate), false);
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'year', date('Y', $parsedPubdate));

		return $publicationDateNode;
	}

}

?>
