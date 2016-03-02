<?php

/**
 * @file plugins/importexport/datacite/classes/DataciteExportDom.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataciteExportDom
 * @ingroup plugins_importexport_datacite_classes
 *
 * @brief DataCite XML export format implementation.
 */


if (!class_exists('DOIExportDom')) { // Bug #7848
	import('plugins.importexport.datacite.classes.DOIExportDom');
}

// XML attributes
define('DATACITE_XMLNS' , 'http://datacite.org/schema/kernel-2.2');
define('DATACITE_XSI_SCHEMALOCATION' , 'http://datacite.org/schema/kernel-2.2 http://schema.datacite.org/meta/kernel-2.2/metadata.xsd');

// Date types
define('DATACITE_DATE_AVAILABLE', 'Available');
define('DATACITE_DATE_ISSUED', 'Issued');
define('DATACITE_DATE_SUBMITTED', 'Submitted');
define('DATACITE_DATE_ACCEPTED', 'Accepted');
define('DATACITE_DATE_CREATED', 'Created');
define('DATACITE_DATE_UPDATED', 'Updated');

// Identifier types
define('DATACITE_IDTYPE_PROPRIETARY', 'publisherId');
define('DATACITE_IDTYPE_EISSN', 'EISSN');
define('DATACITE_IDTYPE_ISSN', 'ISSN');
define('DATACITE_IDTYPE_DOI', 'DOI');

// Title types
define('DATACITE_TITLETYPE_TRANSLATED', 'TranslatedTitle');
define('DATACITE_TITLETYPE_ALTERNATIVE', 'AlternativeTitle');

// Relation types
define('DATACITE_RELTYPE_ISVARIANTFORMOF', 'IsVariantFormOf');
define('DATACITE_RELTYPE_HASPART', 'HasPart');
define('DATACITE_RELTYPE_ISPARTOF', 'IsPartOf');
define('DATACITE_RELTYPE_ISPREVIOUSVERSIONOF', 'IsPreviousVersionOf');
define('DATACITE_RELTYPE_ISNEWVERSIONOF', 'IsNewVersionOf');

// Description types
define('DATACITE_DESCTYPE_ABSTRACT', 'Abstract');
define('DATACITE_DESCTYPE_SERIESINFO', 'SeriesInformation');
define('DATACITE_DESCTYPE_TOC', 'TableOfContents');
define('DATACITE_DESCTYPE_OTHER', 'Other');

class DataciteExportDom extends DOIExportDom {

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
	function DataciteExportDom($request, $plugin, $journal, $objectCache) {
		// Configure the DOM.
		parent::DOIExportDom($request, $plugin, $journal, $objectCache);
	}


	//
	// Public methods
	//
	/**
	 * @see DOIExportDom::generate()
	 */
	function &generate(&$object) {
		$falseVar = false;

		// Declare variables that will contain publication objects.
		$journal = $this->getJournal();
		$issue = null; /* @var $issue Issue */
		$article = null; /* @var $article PublishedArticle */
		$galley = null; /* @var $galley ArticleGalley */
		$articlesByIssue = null;
		$galleysByArticle = null;

		// Retrieve required publication objects (depends on the object to be exported).
		$pubObjects =& $this->retrievePublicationObjects($object);
		extract($pubObjects);

		// Identify an object implementing a SubmissionFile (if any).
		$submissionFile = $galley;

		// Identify the object locale.
		$objectLocalePrecedence = $this->getObjectLocalePrecedence($article, $galley);

		// The publisher is required.
			$publisher = $this->getPublisher($objectLocalePrecedence);

		// The publication date is required.
		$publicationDate = (is_a($article, 'PublishedArticle') ? $article->getDatePublished() : null);
		if (empty($publicationDate)) {
			$publicationDate = $issue->getDatePublished();
		}
		assert(!empty($publicationDate));

		// Create the XML document and its root element.
		$doc =& $this->getDoc();
		$rootElement =& $this->rootElement();
		XMLCustomWriter::appendChild($doc, $rootElement);

		// DOI (mandatory)
		if (($identifierElement =& $this->_identifierElement($object)) === false) return false;
		XMLCustomWriter::appendChild($rootElement, $identifierElement);

		// Creators (mandatory)
		XMLCustomWriter::appendChild($rootElement, $this->_creatorsElement($object, $objectLocalePrecedence, $publisher));

		// Title (mandatory)
		XMLCustomWriter::appendChild($rootElement, $this->_titlesElement($object, $objectLocalePrecedence));

		// Publisher (mandatory)
		XMLCustomWriter::createChildWithText($this->getDoc(), $rootElement, 'publisher', $publisher);

		// Publication Year (mandatory)
		XMLCustomWriter::createChildWithText($this->getDoc(), $rootElement, 'publicationYear', date('Y', strtotime($publicationDate)));

		// Subjects
		if (!empty($article)) {
			$this->_appendNonMandatoryChild($rootElement, $this->_subjectsElement($article, $objectLocalePrecedence));
		}

		// Dates
		XMLCustomWriter::appendChild($rootElement, $this->_datesElement($issue, $article, $submissionFile, $publicationDate));

		// Language
		XMLCustomWriter::createChildWithText($this->getDoc(), $rootElement, 'language', AppLocale::get3LetterIsoFromLocale($objectLocalePrecedence[0]));

		// Resource Type
		$resourceTypeElement =& $this->_resourceTypeElement($object);
		XMLCustomWriter::appendChild($rootElement, $resourceTypeElement);

		// Alternate Identifiers
		$this->_appendNonMandatoryChild($rootElement, $this->_alternateIdentifiersElement($object, $issue, $article, $submissionFile));

		// Related Identifiers
		$this->_appendNonMandatoryChild($rootElement, $this->_relatedIdentifiersElement($object, $articlesByIssue, $galleysByArticle, $issue, $article));

		// Sizes
		$sizesElement =& $this->_sizesElement($object, $article);
		if ($sizesElement) XMLCustomWriter::appendChild($rootElement, $sizesElement);

		// Formats
		if (!empty($submissionFile)) XMLCustomWriter::appendChild($rootElement, $this->_formatsElement($submissionFile));

		// Rights
		$rights = $this->getPrimaryTranslation($journal->getSetting('copyrightNotice', null), $objectLocalePrecedence);
		if (!empty($rights)) XMLCustomWriter::createChildWithText($this->getDoc(), $rootElement, 'rights', PKPString::html2text($rights));

		// Descriptions
		$descriptionsElement =& $this->_descriptionsElement($issue, $article, $objectLocalePrecedence, $articlesByIssue);
		if ($descriptionsElement) XMLCustomWriter::appendChild($rootElement, $descriptionsElement);

		return $doc;
	}


	//
	// Implementation of template methods from DOIExportDom
	//
	/**
	 * @see DOIExportDom::getRootElementName()
	 */
	function getRootElementName() {
		return 'resource';
	}

	/**
	 * @see DOIExportDom::getNamespace()
	 */
	function getNamespace() {
		return DATACITE_XMLNS;
	}

	/**
	 * @see DOIExportDom::getXmlSchemaLocation()
	 */
	function getXmlSchemaLocation() {
		return DATACITE_XSI_SCHEMALOCATION;
	}

	/**
	 * @see DOIExportDom::retrievePublicationObjects()
	 */
	function &retrievePublicationObjects(&$object) {
		// Initialize local variables.
		$nullVar = null;
 		$journal = $this->getJournal();
 		$cache = $this->getCache();

		// Retrieve basic OJS objects.
		$publicationObjects = parent::retrievePublicationObjects($object);

		// Retrieve additional related objects.
		// For articles: Retrieve all galleys and supp files of the article:
		if (is_a($object, 'PublishedArticle')) {
			$article =& $publicationObjects['article'];
			$publicationObjects['galleysByArticle'] =& $this->retrieveGalleysByArticle($article);
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

	/**
	 * @see DOIExportDom::getObjectLocalePrecedence()
	 * @param $article Article
	 * @param $galley Galley
	 */
	function getObjectLocalePrecedence(&$article, &$galley) {
		// Retrieve further locales from the other objects.
		return array_merge($locales, parent::getObjectLocalePrecedence($article, $galley));
	}


	//
	// Private helper methods
	//
	/**
	 * Create an identifier element.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @return XMLNode|DOMImplementation
	 */
	function &_identifierElement(&$object) {
		$doi = $object->getPubId('doi');
		if (empty($doi)) {
			$this->_addError('plugins.importexport.common.export.error.noDoiAssigned', $object->getId());
			return $falseVar;
		}
		if ($this->getTestMode()) {
			$doi = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
		}
		return $this->createElementWithText('identifier', $doi, array('identifierType' => 'DOI'));
	}

	/**
	 * Create the creators element list.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $objectLocalePrecedence array
	 * @param $publisher string
	 * @return XMLNode|DOMImplementation
	 */
	function &_creatorsElement(&$object, $objectLocalePrecedence, $publisher) {
		$cache = $this->getCache();

		$creators = array();
		switch (true) {
			case is_a($object, 'ArticleGalley'):
				// Retrieve the article of the supp file or galley...
				$article =& $cache->get('articles', $object->getArticleId());
				// ...then go on by retrieving the article authors.

			case is_a($object, 'PublishedArticle'):
				// Retrieve the article authors.
				if (!isset($article)) $article =& $object;
				$authors =& $article->getAuthors();
				assert(!empty($authors));
				foreach ($authors as $author) { /* @var $author Author */
					$creators[] = $author->getFullName(true);
				}
				break;

			case is_a($object, 'Issue'):
				$creators[] = $publisher;
				break;
		}

		assert(count($creators) >= 1);
		$creatorsElement =& XMLCustomWriter::createElement($this->getDoc(), 'creators');
		foreach($creators as $creator) {
			XMLCustomWriter::appendChild($creatorsElement, $this->_creatorElement($creator));
		}
		return $creatorsElement;
	}

	/**
	 * Create a single creator element.
	 * @param $creator string
	 * @return XMLNode|DOMImplementation
	 */
	function &_creatorElement($creator) {
		$creatorElement =& XMLCustomWriter::createElement($this->getDoc(), 'creator');
		XMLCustomWriter::createChildWithText($this->getDoc(), $creatorElement, 'creatorName', $creator);
		return $creatorElement;
	}

	/**
	 * Create the titles element list.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $objectLocalePrecedence array
	 * @return XMLNode|DOMImplementation
	 */
	function &_titlesElement(&$object, $objectLocalePrecedence) {
		$cache = $this->getCache();

		// Get an array of localized titles.
		$alternativeTitle = null;
		switch (true) {
			case is_a($object, 'ArticleGalley'):
				// Retrieve the article of the galley...
				$article =& $cache->get('articles', $object->getArticleId());
				// ...then go on by retrieving the article titles.

			case is_a($object, 'PublishedArticle'):
				if (!isset($article)) $article =& $object;
				$titles =& $article->getTitle(null);
				break;

			case is_a($object, 'Issue'):
				$titles = $this->_getIssueInformation($object);
				$alternativeTitle = $this->getPrimaryTranslation($object->getTitle(null), $objectLocalePrecedence);
				break;
		}

		// Order titles by locale precedence.
		$titles = $this->getTranslationsByPrecedence($titles, $objectLocalePrecedence);

		// We expect at least one title.
		assert(count($titles)>=1);

		$titlesElement =& XMLCustomWriter::createElement($this->getDoc(), 'titles');

		// Start with the primary object locale.
		$primaryTitle = array_shift($titles);
		XMLCustomWriter::appendChild($titlesElement, $this->_titleElement($primaryTitle));

		// Then let the translated titles follow.
		foreach($titles as $locale => $title) {
			XMLCustomWriter::appendChild($titlesElement, $this->_titleElement($title, DATACITE_TITLETYPE_TRANSLATED));
		}

		// And finally the alternative title.
		if (!empty($alternativeTitle)) {
			XMLCustomWriter::appendChild($titlesElement, $this->_titleElement($alternativeTitle, DATACITE_TITLETYPE_ALTERNATIVE));
		}

		return $titlesElement;
	}

	/**
	 * Create a single title element.
	 * @param $title string
	 * @param $titleType string One of the DATACITE_TITLETYPE_* constants.
	 * @return XMLNode|DOMImplementation
	 */
	function &_titleElement($title, $titleType = null) {
		$titleElement =& $this->createElementWithText('title', $title);
		if (!is_null($titleType)) {
			XMLCustomWriter::setAttribute($titleElement, 'titleType', $titleType);
		}
		return $titleElement;
	}

	/**
	 * Create the subjects element list.
	 * @param $object PublishedArticle
	 * @param $objectLocalePrecedence array
	 * @return XMLNode|DOMImplementation
	 */
	function &_subjectsElement(&$object, $objectLocalePrecedence) {
		$subjectsElement =& XMLCustomWriter::createElement($this->getDoc(), 'subjects');
		assert(is_a($object, 'PublishedArticle'));
		$keywords = $this->getPrimaryTranslation($object->getSubject(null), $objectLocalePrecedence);
		if (!empty($keywords)) {
			XMLCustomWriter::appendChild($subjectsElement, $this->_subjectElement($keywords));
		}
		return $subjectsElement;
	}

	/**
	 * Create a single subject element.
	 * @param $subject string
	 * @param $subjectScheme string
	 * @return XMLNode|DOMImplementation
	 */
	function &_subjectElement($subject, $subjectScheme = null) {
		$subjectElement =& $this->createElementWithText('subject', $subject);
		if (!empty($subjectScheme)) {
			XMLCustomWriter::setAttribute($subjectElement, 'subjectScheme', $subjectScheme);
		}
		return $subjectElement;
	}

	/**
	 * Create a date element list.
	 * @param $issue Issue
	 * @param $article PublishedArticle
	 * @param $submissionFile SubmissionFile
	 * @param $publicationDate string
	 * @return XMLNode|DOMImplementation
	 */
	function &_datesElement(&$issue, &$article, &$submissionFile, $publicationDate) {
		$datesElement =& XMLCustomWriter::createElement($this->getDoc(), 'dates');
		$dates = array();

		// Submitted date (for articles and galleys): article date submitted.
		if (!empty($article)) {
			$submittedDate = $article->getDateSubmitted();
			if (!empty($submittedDate)) {
				$dates[DATACITE_DATE_SUBMITTED] = $submittedDate;

				// Default accepted date: submitted date.
				$dates[DATACITE_DATE_ACCEPTED] = $submittedDate;
			}
		}

		// Accepted date (for galleys and supp files): article file uploaded.
		if (!empty($submissionFile)) {
			$acceptedDate = $submissionFile->getDateUploaded();
			if (!empty($acceptedDate)) {
				$dates[DATACITE_DATE_ACCEPTED] = $acceptedDate;
			}
		}

		// Issued date: publication date.
		$dates[DATACITE_DATE_ISSUED] = $publicationDate;

		// Available date: issue open access date.
		$availableDate = $issue->getOpenAccessDate();
		if (!empty($availableDate)) {
			$dates[DATACITE_DATE_AVAILABLE] = $availableDate;
		}

		// Last modified date (for articles): last modified date.
		if (!empty($article) && empty($submissionFile)) {
			$dates[DATACITE_DATE_UPDATED] = $article->getLastModified();
		}

		// Create the date elements for all dates.
		foreach($dates as $dateType => $date) {
			XMLCustomWriter::appendChild($datesElement, $this->_dateElement($dateType, $date));
		}

		return $datesElement;
	}

	/**
	 * Create a single date element.
	 * @param $dateType string One of the DATACITE_DATE_* constants.
	 * @param $date string
	 * @return XMLNode|DOMImplementation
	 */
	function &_dateElement($dateType, $date) {
		// Format the date.
		assert(!empty($date));
		$date = date('Y-m-d', strtotime($date));

		// Create the date element.
		return $this->createElementWithText('date', $date, array('dateType' => $dateType));
	}

	/**
	 * Create a resource type element.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @return XMLNode|DOMImplementation
	 */
	function &_resourceTypeElement($object) {
		switch (true) {
			case is_a($object, 'Issue'):
				$resourceType = 'Journal Issue';
				break;

			case is_a($object, 'PublishedArticle'):
			case is_a($object, 'ArticleGalley'):
				$resourceType = 'Article';
				break;

			default:
				assert(false);
		}

		// Create the resourceType element.
		return $this->createElementWithText('resourceType', $resourceType, array('resourceTypeGeneral' => 'Text'));
	}

	/**
	 * Generate alternate identifiers element list.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $issue Issue
	 * @param $article PublishedArticle
	 * @param $submissionFile SubmissionFile
	 * @return XMLNode|DOMImplementation
	 */
	function &_alternateIdentifiersElement(&$object, &$issue, &$article, &$submissionFile) {
		$journal = $this->getJournal();
		$alternateIdentifiersElement =& XMLCustomWriter::createElement($this->getDoc(), 'alternateIdentifiers');

		// Proprietary ID
		$proprietaryId = $this->getProprietaryId($journal, $issue, $article, $submissionFile);
		XMLCustomWriter::appendChild(
			$alternateIdentifiersElement,
			$this->createElementWithText(
				'alternateIdentifier', $proprietaryId,
				array('alternateIdentifierType' => DATACITE_IDTYPE_PROPRIETARY)
			)
		);

		// ISSN - for issues only.
		if (is_a($object, 'Issue')) {
			$onlineIssn = $journal->getSetting('onlineIssn');
			if (!empty($onlineIssn)) {
				XMLCustomWriter::appendChild(
					$alternateIdentifiersElement,
					$this->createElementWithText(
						'alternateIdentifier', $onlineIssn,
						array('alternateIdentifierType' => DATACITE_IDTYPE_EISSN)
					)
				);
			}

			$printIssn = $journal->getSetting('printIssn');
			if (!empty($printIssn)) {
				XMLCustomWriter::appendChild(
					$alternateIdentifiersElement,
					$this->createElementWithText(
						'alternateIdentifier', $printIssn,
						array('alternateIdentifierType' => DATACITE_IDTYPE_ISSN)
					)
				);
			}
		}

		return $alternateIdentifiersElement;
	}


	/**
	 * Generate related identifiers element list.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $articlesByIssue array
	 * @param $galleysByArticle array
	 * @param $issue Issue
	 * @param $article PublishedArticle
	 * @return XMLNode|DOMImplementation
	 */
	function &_relatedIdentifiersElement(&$object, &$articlesByIssue, &$galleysByArticle, &$issue, &$article) {
		$journal = $this->getJournal();
		$relatedIdentifiersElement = XMLCustomWriter::createElement($this->getDoc(), 'relatedIdentifiers');

		switch (true) {
			case is_a($object, 'Issue'):
				// Parts: articles in this issue.
				assert(is_array($articlesByIssue));
				foreach($articlesByIssue as $articleInIssue) {
					$doi =& $this->_relatedIdentifierElement($articleInIssue, DATACITE_RELTYPE_HASPART);
					if (!is_null($doi)) XMLCustomWriter::appendChild($relatedIdentifiersElement, $doi);
					unset($articleInIssue, $doi);
				}
				break;

			case is_a($object, 'PublishedArticle'):
				// Part of: issue.
				assert(is_a($issue, 'Issue'));
				$doi =& $this->_relatedIdentifierElement($issue, DATACITE_RELTYPE_ISPARTOF);
				if (!is_null($doi)) XMLCustomWriter::appendChild($relatedIdentifiersElement, $doi);
				unset($doi);

				// Parts: galleys and supp files.
				assert(is_array($galleysByArticle));
				$relType = DATACITE_RELTYPE_HASPART;
				foreach($galleysByArticle as $galleyInArticle) {
					$doi =& $this->_relatedIdentifierElement($galleyInArticle, $relType);
					if (!is_null($doi)) XMLCustomWriter::appendChild($relatedIdentifiersElement, $doi);
					unset($galleyInArticle, $doi);
				}
				break;

			case is_a($object, 'SubmissionFile'):
				// Part of: article.
				assert(is_a($article, 'Article'));
				$doi =& $this->_relatedIdentifierElement($article, DATACITE_RELTYPE_ISPARTOF);
				if (!is_null($doi)) XMLCustomWriter::appendChild($relatedIdentifiersElement, $doi);
				break;
		}

		return $relatedIdentifiersElement;
	}

	/**
	 * Create an identifier element with the object's DOI.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $relationType string One of the DATACITE_RELTYPE_* constants.
	 * @return XMLNode|DOMImplementation|null Can be null if the given ID Type
	 *  has not been assigned to the given object.
	 */
	function &_relatedIdentifierElement(&$object, $relationType) {
		$id = $object->getPubId('doi');

		if (empty($id)) {
			return $nullVar;
			$nullVar = null;
		}
		if ($this->getTestMode()) {
			$id = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $id);
		}

		return $this->createElementWithText(
			'relatedIdentifier', $id,
			array(
				'relatedIdentifierType' => DATACITE_IDTYPE_DOI,
				'relationType' => $relationType
			)
		);
	}

	/**
	 * Create a sizes element list.
	 * @param $object Issue|PublishedArticle|SubmissionFile
	 * @param $article PublishedArticle|null
	 * @return XMLNode|DOMImplementation|null Can be null if a size
	 *  cannot be identified for the given object.
	 */
	function &_sizesElement(&$object, &$article) {
		switch (true) {
			case is_a($object, 'Issue'):
				$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
				$files = $issueGalleyDao->getByIssueId($object->getId());
				break;

			case is_a($object, 'PublishedArticle'):
				$pages = $object->getPages();
				$files = array();
				break;

			case is_a($object, 'SubmissionFile'):
				if (is_a($object, 'ArticleGalley')) {
					// The galley represents the article.
					$pages = $article->getPages();
				}
				$files = array(&$object);
				break;

			default:
				assert(false);
		}

		$sizes = array();
		if (!empty($pages)) {
			$sizes[] = $pages . ' ' . __('editor.issues.pages');
		}
		foreach($files as $file) { /* @var $file PKPFile */
			$fileSize = round(((int)$file->getFileSize()) / 1024);
			if ($fileSize >= 1024) {
				$fileSize = round($fileSize / 1024, 2);
				$sizes[] = $fileSize . ' MB';
			} elseif ($fileSize >= 1) {
				$sizes[] = $fileSize . ' kB';
			}
			unset($file);
		}

		$sizesElement = null;
		if (!empty($sizes)) {
			$sizesElement =& XMLCustomWriter::createElement($this->getDoc(), 'sizes');
			foreach($sizes as $size) {
				XMLCustomWriter::createChildWithText($this->getDoc(), $sizesElement, 'size', $size);
			}
		}
		return $sizesElement;
	}

	/**
	 * Create a formats element list.
	 * @param $submissionFile SubmissionFile
	 * @return XMLNode|DOMImplementation|null Can be null if a format
	 *  cannot be identified for the given object.
	 */
	function &_formatsElement(&$submissionFile) {
		$format = $submissionFile->getFileType();
		if (empty($format)) {
			$nullVar = null;
			return $nullVar;
		}

		$formatsElement =& XMLCustomWriter::createElement($this->getDoc(), 'formats');
		XMLCustomWriter::createChildWithText($this->getDoc(), $formatsElement, 'format', $format);
		return $formatsElement;
	}

	/**
	 * Create a descriptions element list.
	 * @param $issue Issue
	 * @param $article PublishedArticle
	 * @param $objectLocalePrecedence array
	 * @param $articlesByIssue array
	 * @return XMLNode|DOMImplementation|null Can be null if no descriptions
	 *  can be identified for the given object.
	 */
	function &_descriptionsElement(&$issue, &$article, $objectLocalePrecedence, &$articlesByIssue) {
		$descriptions = array();

		if (isset($article)) {
			// Articles and galleys.
			$articleAbstract = $this->getPrimaryTranslation($article->getAbstract(null), $objectLocalePrecedence);
			if (!empty($articleAbstract)) $descriptions[DATACITE_DESCTYPE_ABSTRACT] = $articleAbstract;
		}

		if (isset($article)) {
			// Articles, galleys and supp files.
			$descriptions[DATACITE_DESCTYPE_SERIESINFO] = $this->_getIssueInformation($issue, $objectLocalePrecedence);
		} else {
			// Issues.
			$issueDesc = $this->getPrimaryTranslation($issue->getDescription(null), $objectLocalePrecedence);
			if (!empty($issueDesc)) $descriptions[DATACITE_DESCTYPE_OTHER] = $issueDesc;
			$descriptions[DATACITE_DESCTYPE_TOC] = $this->_getIssueToc($articlesByIssue, $objectLocalePrecedence);
		}

		$descriptionsElement = null;
		if (!empty($descriptions)) {
			$descriptionsElement =& XMLCustomWriter::createElement($this->getDoc(), 'descriptions');
			foreach($descriptions as $descType => $description) {
				XMLCustomWriter::appendChild(
					$descriptionsElement,
					$this->createElementWithText('description', $description, array('descriptionType' => $descType))
				);
			}
		}
		return $descriptionsElement;
	}

	/**
	 * Construct an issue title from the journal title
	 * and the issue identification.
	 * @param $issue Issue
	 * @param $objectLocalePrecedence array
	 * @return array|string An array of localized issue titles
	 *  or a string if a locale has been given.
	 */
	function _getIssueInformation(&$issue, $objectLocalePrecedence = null) {
		$issueIdentification = $issue->getIssueIdentification();
		assert(!empty($issueIdentification));

		$journal = $this->getJournal();
		if (is_null($objectLocalePrecedence)) {
			$issueInfo = array();
			foreach ($journal->getName(null) as $locale => $journalTitle) {
				$issueInfo[$locale] = "$journalTitle, $issueIdentification";
			}
		} else {
			$issueInfo = $this->getPrimaryTranslation($journal->getName(null), $objectLocalePrecedence);
			if (!empty($issueInfo)) {
				$issueInfo .= ', ';
			}
			$issueInfo .= $issueIdentification;
		}
		return $issueInfo;
	}

	/**
	 * Construct a table of content from an article list.
	 * @param $articlesByIssue
	 * @param $objectLocalePrecedence array
	 * @return string
	 */
	function _getIssueToc(&$articlesByIssue, $objectLocalePrecedence) {
		assert(is_array($articlesByIssue));
		$toc = '';
		foreach($articlesByIssue as $articleInIssue) {
			$currentEntry = $this->getPrimaryTranslation($articleInIssue->getTitle(null), $objectLocalePrecedence);
			assert(!empty($currentEntry));
			$pages = $articleInIssue->getPages();
			if (!empty($pages)) {
				$currentEntry .= '...' . $pages;
			}
			$toc .= $currentEntry . "<br />";
			unset($articleInIssue);
		}
		return $toc;
	}

	/**
	 * Datacite does not allow empty nodes. So we have to
	 * check nodes before we add them.
	 * @param $parentNode XmlNode|DOMElement
	 * @param $child XmlNode|DOMElement
	 */
	function _appendNonMandatoryChild(&$parentNode, &$child) {
		if (is_a($child, 'XMLNode')) {
			$childChildren = $child->getChildren();
			$childEmpty = empty($childChildren);
		} else {
			$childEmpty = !$child->hasChildNodes();
		}
		if ($childEmpty) return;
		XMLCustomWriter::appendChild($parentNode, $child);
	}
}

?>
