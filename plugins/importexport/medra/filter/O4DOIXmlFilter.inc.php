<?php

/**
 * @file plugins/importexport/medra/filter/O4DOIXmlFilter.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class O4DOIXmlFilter
 * @ingroup plugins_importexport_medra
 *
 * @brief Basis class for converting objects (issues, articles, galleys) to a O4DOI XML document.
 */

// Notification types
define('O4DOI_NOTIFICATION_TYPE_NEW', '06');
define('O4DOI_NOTIFICATION_TYPE_UPDATE', '07');

// ID types
define('O4DOI_ID_TYPE_PROPRIETARY', '01');
define('O4DOI_ID_TYPE_DOI', '06');
define('O4DOI_ID_TYPE_ISSN', '07');

// Text formats
define('O4DOI_TEXTFORMAT_ASCII', '00');

// Title types
define('O4DOI_TITLE_TYPE_FULL', '01');
define('O4DOI_TITLE_TYPE_ISSUE', '07');

// Publishing roles
define('O4DOI_PUBLISHING_ROLE_PUBLISHER', '01');

// Product forms
define('O4DOI_PRODUCT_FORM_PRINT', 'JB');
define('O4DOI_PRODUCT_FORM_ELECTRONIC', 'JD');

// ePublication formats
// S. ONIX List 11 (https://onix-codelists.io/codelist/11)
// We will consider only HTML and PDF
define('O4DOI_EPUB_FORMAT_HTML', '01');
define('O4DOI_EPUB_FORMAT_PDF', '02');

// Date formats
define('O4DOI_DATE_FORMAT_YYYY', '06');

// Extent types
define('O4DOI_EXTENT_TYPE_FILESIZE', '22');

// Extent units
define('O4DOI_EXTENT_UNIT_BYTES', '17');

// Contributor roles
define('O4DOI_CONTRIBUTOR_ROLE_ACTUAL_AUTHOR', 'A01');

// Language roles
define('O4DOI_LANGUAGE_ROLE_LANGUAGE_OF_TEXT', '01');

// Subject schemes
define('O4DOI_SUBJECT_SCHEME_PUBLISHER', '23');
define('O4DOI_SUBJECT_SCHEME_PROPRIETARY', '24');

// Text type codes
define('O4DOI_TEXT_TYPE_MAIN_DESCRIPTION', '01');

// Relation codes
define('O4DOI_RELATION_INCLUDES', '80');
define('O4DOI_RELATION_IS_PART_OF', '81');
define('O4DOI_RELATION_IS_A_NEW_VERSION_OF', '82');
define('O4DOI_RELATION_HAS_A_NEW_VERSION', '83');
define('O4DOI_RELATION_IS_A_DIFFERENT_FORM_OF', '84');
define('O4DOI_RELATION_IS_A_LANGUAGE_VERSION_OF', '85');
define('O4DOI_RELATION_IS_MANIFESTED_IN', '89');
define('O4DOI_RELATION_IS_A_MANIFESTATION_OF', '90');


import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');


class O4DOIXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}

	/**
	 * Get whether the object exported is considered as work
	 * @param $context Context
	 * @param $plugin DOIPubIdExportPlugin
	 * @return boolean
	 */
	function isWork($context, $plugin) {
		return true;
	}

	/**
	 * Get root node name
	 * @return string
	 */
	function getRootNodeName() {
		assert(false);
	}

	//
	// Common filter functions
	//
	/**
	 * Create and return the root node.
	 * @param $doc DOMDocument
	 * @param $rootNodeName string
	 * @return DOMElement
	 */
	function createRootNode($doc, $rootNodeName) {
		$deployment = $this->getDeployment();
		$rootNode = $doc->createElementNS($deployment->getNamespace(), $rootNodeName);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', $deployment->getXmlSchemaInstance());
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());
		return $rootNode;
	}

	/**
	 * Create and return the head node.
	 * @param $doc DOMDocument
	 * @return DOMElement
	 */
	function createHeadNode($doc) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		$headNode = $doc->createElementNS($deployment->getNamespace(), 'Header');
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'FromCompany', htmlspecialchars($plugin->getSetting($context->getId(), 'fromCompany'), ENT_COMPAT, 'UTF-8')));
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'FromPerson',  htmlspecialchars($plugin->getSetting($context->getId(), 'fromName'), ENT_COMPAT, 'UTF-8')));
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'FromEmail',  htmlspecialchars($plugin->getSetting($context->getId(), 'fromEmail'), ENT_COMPAT, 'UTF-8')));
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ToCompany',  'mEDRA'));
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'SentDate',  date('YmdHi')));
		// Message note
		$app = PKPApplication::getApplication();
		$name = $app->getName();
		$version = $app->getCurrentVersion();
		$versionString = $version->getVersionString();
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'MessageNote',  "This dataset was exported with $name, version $versionString."));
		return $headNode;
	}

	/**
	 * Generate O4DOI serial publication node.
	 * @param $doc DOMDocument
	 * @param $journalLocalePrecedence array
	 * @param $epubFormat O4DOI_EPUB_FORMAT_*
	 * @return DOMElement
	 */
	function createSerialPublicationNode($doc, $journalLocalePrecedence, $epubFormat = null) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		$serialPublicationNode = $doc->createElementNS($deployment->getNamespace(), 'SerialPublication');
		// Serial Work (mandatory)
		$serialPublicationNode->appendChild($this->createSerialWorkNode($doc, $journalLocalePrecedence));
		// Electronic Serial Version
		$onlineIssn = $context->getSetting('onlineIssn');
		$serialPublicationNode->appendChild($this->createSerialVersionNode($doc,  $onlineIssn, O4DOI_PRODUCT_FORM_ELECTRONIC, $epubFormat));
		// Print Serial Version
		if (($printIssn = $context->getSetting('printIssn')) && $this->isWork($context, $plugin)) {
			$serialPublicationNode->appendChild($this->createSerialVersionNode($doc,  $printIssn, O4DOI_PRODUCT_FORM_PRINT, null));
		}
		return $serialPublicationNode;
	}

	/**
	 * Generate O4DOI serial work node.
	 * @param $doc DOMDocument
	 * @param $journalLocalePrecedence array
	 * @return DOMElement
	 */
	function createSerialWorkNode($doc, $journalLocalePrecedence) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		$serialWorkNode = $doc->createElementNS($deployment->getNamespace(), 'SerialWork');
		// Title (mandatory)
		$journalTitles = $this->getTranslationsByPrecedence($context->getName(null), $journalLocalePrecedence);
		assert(!empty($journalTitles));
		foreach($journalTitles as $locale => $journalTitle) {
			$serialWorkNode->appendChild($this->createTitleNode($doc, $locale, $journalTitle, O4DOI_TITLE_TYPE_FULL));
		}
		// Publisher
		$serialWorkNode->appendChild($this->createPublisherNode($doc, $journalLocalePrecedence));
		// Country of Publication (mandatory)
		$serialWorkNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'CountryOfPublication',  htmlspecialchars($plugin->getSetting($context->getId(), 'publicationCountry'), ENT_COMPAT, 'UTF-8')));
		return $serialWorkNode;
	}

	/**
	 * Create a title node.
	 * @param $doc DOMDocument
	 * @param $locale string e.g. 'en_US'
	 * @param $localizedTitle string
	 * @param $titleType string One of the O4DOI_TITLE_TYPE_* constants.
	 * @return DOMElement
	 */
	function createTitleNode($doc, $locale, $localizedTitle, $titleType) {
		$deployment = $this->getDeployment();
		$titleNode = $doc->createElementNS($deployment->getNamespace(), 'Title');
		// Text format
		$titleNode->setAttribute('textformat', O4DOI_TEXTFORMAT_ASCII);
		// Language
		$language = AppLocale::get3LetterIsoFromLocale($locale);
		assert(!empty($language));
		$titleNode->setAttribute('language', $language);
		// Title type (mandatory)
		$titleNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'TitleType', $titleType));
		// Title text (mandatory)
		$titleNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'TitleText', htmlspecialchars(PKPString::html2text($localizedTitle), ENT_COMPAT, 'UTF-8')));
		return $titleNode;
	}

	/**
	 * Create a publisher node.
	 * @param $doc DOMDocument
	 * @param $journalLocalePrecedence array
	 * @return DOMElement
	 */
	function createPublisherNode($doc, $journalLocalePrecedence) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$publisherNode = $doc->createElementNS($deployment->getNamespace(), 'Publisher');
		// Publishing role (mandatory)
		$publisherNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'PublishingRole', O4DOI_PUBLISHING_ROLE_PUBLISHER));
		// Publisher name (mandatory)
		$publisher = $context->getSetting('publisherInstitution');
		if (empty($publisher)) {
			// Use the journal title if no publisher is set.
			// This corresponds to the logic implemented for OAI interfaces, too.
			$publisher = $this->getPrimaryTranslation($context->getName(null), $journalLocalePrecedence);
		}
		assert(!empty($publisher));
		$publisherNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'PublisherName', htmlspecialchars($publisher, ENT_COMPAT, 'UTF-8')));
		return $publisherNode;
	}

	/**
	 * Create a serial version node.
	 * @param $doc DOMDocument
	 * @param $issn string
	 * @param $productForm One of the O4DOI_PRODUCT_FORM_* constants
	 * @param $epubFormat O4DOI_EPUB_FORMAT_*
	 * @return DOMElement
	 */
	function createSerialVersionNode($doc, $issn, $productForm, $epubFormat = null) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$serialVersionNode = $doc->createElementNS($deployment->getNamespace(), 'SerialVersion');
		// Proprietary Journal Identifier
		if ($productForm == O4DOI_PRODUCT_FORM_ELECTRONIC) {
			$serialVersionNode->appendChild($this->createIdentifierNode($doc, 'Product', O4DOI_ID_TYPE_PROPRIETARY, $context->getId()));
		}
		// ISSN
		if (!empty($issn)) {
			$issn = PKPString::regexp_replace('/[^0-9]/', '', $issn);
			$serialVersionNode->appendChild($this->createIdentifierNode($doc, 'Product', O4DOI_ID_TYPE_ISSN, $issn));
		}
		// Product Form
		$serialVersionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ProductForm', $productForm));
		if ($productForm == O4DOI_PRODUCT_FORM_ELECTRONIC) {
			// ePublication Format
			if ($epubFormat) $serialVersionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'EpubFormat', $epubFormat));
			// ePublication Format Description
			$serialVersionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'EpubFormatDescription', 'Open Journal Systems (OJS)'));
		}
		return $serialVersionNode;
	}

	/**
	 * Create the journal issue node.
	 * @param $doc DOMDocument
	 * @param $issue Issue
	 * @param $journalLocalePrecedence array
	 * @return DOMElement
	 */
	function createJournalIssueNode($doc, $issue, $journalLocalePrecedence) {
		$deployment = $this->getDeployment();
		$journalIssueNode = $doc->createElementNS($deployment->getNamespace(), 'JournalIssue');
		// Volume
		$volume = $issue->getVolume();
		if (!empty($volume) && $issue->getShowVolume()) {
			$journalIssueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'JournalVolumeNumber', htmlspecialchars($volume, ENT_COMPAT, 'UTF-8')));
		}
		// Number
		$number = $issue->getNumber();
		if (!empty($number) && $issue->getShowNumber()) {
			$journalIssueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'JournalIssueNumber', htmlspecialchars($number, ENT_COMPAT, 'UTF-8')));
		}
		// Identification
		$identification = $issue->getIssueIdentification();
		if (!empty($identification)) {
			$journalIssueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'JournalIssueDesignation', htmlspecialchars($identification, ENT_COMPAT, 'UTF-8')));
		}
		assert(!(empty($number) && empty($identification)));
		// Nominal Year
		$year = (string) $issue->getYear();
		$yearlen = strlen($year);
		if ($issue->getShowYear() && !empty($year) && ($yearlen == 2 || $yearlen == 4)) {
			$issueDateNode = $doc->createElementNS($deployment->getNamespace(), 'JournalIssueDate');
			$issueDateNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'DateFormat', O4DOI_DATE_FORMAT_YYYY));
			// Try to extend the year if necessary.
			if ($yearlen == 2) {
				// Assume that the issue date will never be
				// more than one year in the future.
				if ((int)$year <= (int)date('y')+1) {
					$year = '20' . $year;
				} else {
					$year = '19' . $year;
				}
			}
			$issueDateNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'Date', $year));
			$journalIssueNode->appendChild($issueDateNode);
		}
		return $journalIssueNode;
	}

	/**
	 * Create a related work or product node.
	 * @param $doc DOMDocument
	 * @param $workOrProduct string
	 * @param $relationCode string One of the O4DOI_RELATION_* constants.
	 * @param $ids array
	 * @return DOMElement
	 */
	function createRelatedNode($doc, $workOrProduct, $relationCode, $ids) {
		$deployment = $this->getDeployment();
		$relatedNode = $doc->createElementNS($deployment->getNamespace(), "Related$workOrProduct");
		// Relation code (mandatory)
		$relatedNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'RelationCode', $relationCode));
		// Work/Product ID (mandatory)
		foreach($ids as $idType => $id) {
			$relatedNode->appendChild($this->createIdentifierNode($doc, $workOrProduct, $idType, $id));
		}
		return $relatedNode;
	}

	/**
	 * Create a work or product id node.
	 * @param $doc DOMDocument
	 * @param $workOrProduct string "Work" or "Product"
	 * @param $idType string One of the O4DOI_ID_TYPE_* constants
	 * @param $id string The ID.
	 * @return DOMElement
	 */
	function createIdentifierNode($doc, $workOrProduct, $idType, $id) {
		$deployment = $this->getDeployment();
		$productIdentifierNode = $doc->createElementNS($deployment->getNamespace(), "${workOrProduct}Identifier");
		// ID type (mandatory)
		$productIdentifierNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), "${workOrProduct}IDType", $idType));
		// ID (mandatory)
		$productIdentifierNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'IDValue', $id));
		return $productIdentifierNode;
	}

	/**
	 * Create an extent node.
	 * @param $doc DOMDocument
	 * @param $file PKPFile
	 * @return DOMElement
	 */
	 function createExtentNode($doc, $file) {
		 $deployment = $this->getDeployment();
		 $extentNode = $doc->createElementNS($deployment->getNamespace(), 'Extent');
		 // Extent type
		 $extentNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ExtentType', O4DOI_EXTENT_TYPE_FILESIZE));
		 // Extent value
		 $extentNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ExtentValue',  $file->getFileSize()));
		 // Extent unit
		 $extentNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ExtentUnit',  O4DOI_EXTENT_UNIT_BYTES));
		 return $extentNode;
	 }

	 /**
	  * Create a description text node.
	  * @param $doc DOMDocument
	  * @param $locale string
	  * @param $description string
	  * @return DOMElement
	  */
	function createOtherTextNode($doc, $locale, $description) {
		$deployment = $this->getDeployment();
		$otherTextNode = $doc->createElementNS($deployment->getNamespace(), 'OtherText');
		// Text Type
		$otherTextNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'TextTypeCode', O4DOI_TEXT_TYPE_MAIN_DESCRIPTION));
		// Text
		$language = AppLocale::get3LetterIsoFromLocale($locale);
		assert(!empty($language));
		$otherTextNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'Text', htmlspecialchars(PKPString::html2text($description), ENT_COMPAT, 'UTF-8')));
		$node->setAttribute('textformat', O4DOI_TEXTFORMAT_ASCII);
		$node->setAttribute('language', $language);
		return $otherTextNode;
	}

	 //
	 // Helper functions
	 //
	 /**
	  * Get DOIStructuralType
	  * @return string
	  */
	 function getDOIStructuralType() {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		if ($this->isWork($context, $plugin)) {
			return 'Abstraction';
		} else {
			return 'DigitalFixation';
		}
	 }

	/**
	 * Identify the locale precedence for this export.
	 * @param $context Context
	 * @param $article PublishedArticle
	 * @param $galley ArticleGalley
	 * @return array A list of valid PKP locales in descending
	 *  order of priority.
	 */
	function getObjectLocalePrecedence($context, $article, $galley) {
		$locales = array();
		if (is_a($galley, 'ArticleGalley') && AppLocale::isLocaleValid($galley->getLocale())) {
			$locales[] = $galley->getLocale();
		}
		if (is_a($article, 'Submission')) {
			// First try to translate the article language into a locale.
			$articleLocale = $this->translateLanguageToLocale($article->getLanguage());
			if (!is_null($articleLocale)) {
				$locales[] = $articleLocale;
			}

			// Use the article locale as fallback only
			// as this is the primary locale of article meta-data, not
			// necessarily of the article itself.
			if(AppLocale::isLocaleValid($article->getLocale())) {
				$locales[] = $article->getLocale();
			}
		}

		// Use the journal locale as fallback.
		$locales[] = $context->getPrimaryLocale();

		// Use form locales as fallback.
		$formLocales = array_keys($context->getSupportedFormLocaleNames());
		// Sort form locales alphabetically so that
		// we get a well-defined order.
		sort($formLocales);
		foreach($formLocales as $formLocale) {
			if (!in_array($formLocale, $locales)) $locales[] = $formLocale;
		}

		assert(!empty($locales));
		return $locales;
	}

	/**
	 * Try to translate an ISO language code to an OJS locale.
	 * @param $language string 2- or 3-letter ISO language code
	 * @return string|null An OJS locale or null if no matching
	 *  locale could be found.
	 */
	function translateLanguageToLocale($language) {
		$locale = null;
		if (strlen($language) == 2) {
			$language = AppLocale::get3LetterFrom2LetterIsoLanguage($language);
		}
		if (strlen($language) == 3) {
			$language = AppLocale::getLocaleFrom3LetterIso($language);
		}
		if (AppLocale::isLocaleValid($language)) {
			$locale = $language;
		}
		return $locale;
	}

	/**
	 * Identify the primary translation from an array of
	 * localized data.
	 * @param $localizedData array An array of localized
	 *  data (key: locale, value: localized data).
	 * @param $localePrecedence array An array of locales
	 *  by descending priority.
	 * @return mixed|null The value of the primary locale
	 *  or null if no primary translation could be found.
	 */
	function getPrimaryTranslation($localizedData, $localePrecedence) {
		// Check whether we have localized data at all.
		if (!is_array($localizedData) || empty($localizedData)) return null;

		// Try all locales from the precedence list first.
		foreach($localePrecedence as $locale) {
			if (isset($localizedData[$locale]) && !empty($localizedData[$locale])) {
				return $localizedData[$locale];
			}
		}

		// As a fallback: use any translation by alphabetical
		// order of locales.
		ksort($localizedData);
		foreach($localizedData as $locale => $value) {
			if (!empty($value)) return $value;
		}

		// If we found nothing (how that?) return null.
		return null;
	}

	/**
	 * Re-order localized data by locale precedence.
	 * @param $localizedData array An array of localized
	 *  data (key: locale, value: localized data).
	 * @param $localePrecedence array An array of locales
	 *  by descending priority.
	 * @return array Re-ordered localized data.
	 */
	function getTranslationsByPrecedence($localizedData, $localePrecedence) {
		$reorderedLocalizedData = array();

		// Check whether we have localized data at all.
		if (!is_array($localizedData) || empty($localizedData)) return $reorderedLocalizedData;

		// Order by explicit locale precedence first.
		foreach($localePrecedence as $locale) {
			if (isset($localizedData[$locale]) && !empty($localizedData[$locale])) {
				$reorderedLocalizedData[$locale] = $localizedData[$locale];
			}
			unset($localizedData[$locale]);
		}

		// Order any remaining values alphabetically by locale
		// and amend the re-ordered array.
		ksort($localizedData);
		$reorderedLocalizedData = array_merge($reorderedLocalizedData, $localizedData);

		return $reorderedLocalizedData;
	}

}

?>
