<?php

/**
 * @file plugins/metadata/dc11/filter/Dc11SchemaArticleAdapter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Dc11SchemaArticleAdapter
 * @ingroup plugins_metadata_dc11_filter
 * @see Article
 * @see PKPDc11Schema
 *
 * @brief Abstract base class for meta-data adapters that
 *  injects/extracts Dublin Core schema compliant meta-data into/from
 *  an PublishedSubmission object.
 */


import('lib.pkp.classes.metadata.MetadataDataObjectAdapter');

class Dc11SchemaArticleAdapter extends MetadataDataObjectAdapter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getClassName()
	 */
	function getClassName() {
		return 'plugins.metadata.dc11.filter.Dc11SchemaArticleAdapter';
	}


	//
	// Implement template methods from MetadataDataObjectAdapter
	//
	/**
	 * @see MetadataDataObjectAdapter::injectMetadataIntoDataObject()
	 * @param $metadataDescription MetadataDescription
	 * @param $targetDataObject Article
	 */
	function &injectMetadataIntoDataObject(&$metadataDescription, &$targetDataObject) {
		// Not implemented
		assert(false);
	}

	/**
	 * @see MetadataDataObjectAdapter::extractMetadataFromDataObject()
	 * @param $article Article
	 * @return MetadataDescription
	 */
	function &extractMetadataFromDataObject(&$article) {
		assert(is_a($article, 'Article'));

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_SUBMISSION);

		// Retrieve data that belongs to the article.
		// FIXME: Retrieve this data from the respective entity DAOs rather than
		// from the OAIDAO once we've migrated all OAI providers to the
		// meta-data framework. We're using the OAIDAO here because it
		// contains cached entities and avoids extra database access if this
		// adapter is called from an OAI context.
		$oaiDao = DAORegistry::getDAO('OAIDAO'); /* @var $oaiDao OAIDAO */
		$journal = $oaiDao->getJournal($article->getJournalId());
		$section = $oaiDao->getSection($article->getSectionId());
		if (is_a($article, 'PublishedSubmission')) { /* @var $article PublishedSubmission */
			$issue = $oaiDao->getIssue($article->getIssueId());
		} else $issue = null;

		$dc11Description = $this->instantiateMetadataDescription();

		// Title
		$this->_addLocalizedElements($dc11Description, 'dc:title', $article->getFullTitle(null));

		// Creator
		$authors = $article->getAuthors();
		foreach($authors as $author) {
			$dc11Description->addStatement('dc:creator', $author->getFullName(false, true));
		}

		// Subject
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$supportedLocales = array_keys(AppLocale::getSupportedFormLocales());
		$subjects = array_merge_recursive(
			(array) $submissionKeywordDao->getKeywords($article->getId(), $supportedLocales),
			(array) $submissionSubjectDao->getSubjects($article->getId(), $supportedLocales)
		);
		$this->_addLocalizedElements($dc11Description, 'dc:subject', $subjects);

		// Description
		$this->_addLocalizedElements($dc11Description, 'dc:description', $article->getAbstract(null));

		// Publisher
		$publisherInstitution = $journal->getData('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publishers = array($journal->getPrimaryLocale() => $publisherInstitution);
		} else {
			$publishers = $journal->getName(null); // Default
		}
		$this->_addLocalizedElements($dc11Description, 'dc:publisher', $publishers);

		// Contributor
		$contributors = (array) $article->getSponsor(null);
		foreach ($contributors as $locale => $contributor) {
			$contributors[$locale] = array_map('trim', explode(';', $contributor));
		}
		$this->_addLocalizedElements($dc11Description, 'dc:contributor', $contributors);


		// Date
		if (is_a($article, 'PublishedSubmission')) {
			if ($article->getDatePublished()) $dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($article->getDatePublished())));
			elseif (isset($issue) && $issue->getDatePublished()) $dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($issue->getDatePublished())));
		}

		// Type
		$driverType = 'info:eu-repo/semantics/article';
		$dc11Description->addStatement('dc:type', $driverType, METADATA_DESCRIPTION_UNKNOWN_LOCALE);
		$types = $section->getIdentifyType(null);
		$types = array_merge_recursive(
			empty($types)?array(AppLocale::getLocale() => __('metadata.pkp.peerReviewed')):$types,
			(array) $article->getType(null)
		);
		$this->_addLocalizedElements($dc11Description, 'dc:type', $types);
		$driverVersion = 'info:eu-repo/semantics/publishedVersion';
		$dc11Description->addStatement('dc:type', $driverVersion, METADATA_DESCRIPTION_UNKNOWN_LOCALE);


		// Format
		if (is_a($article, 'PublishedSubmission')) {
			$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
			$galleys = $articleGalleyDao->getBySubmissionId($article->getId());
			$formats = array();
			while ($galley = $galleys->next()) {
				$dc11Description->addStatement('dc:format', $galley->getFileType());
			}
		}

		// Identifier: URL
		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$request = Application::get()->getRequest();
		$includeUrls = $journal->getSetting('publishingMode') != PUBLISHING_MODE_NONE || $issueAction->subscribedUser($request->getUser(), $journal, null, $article->getId());
		if (is_a($article, 'PublishedSubmission') && $includeUrls) {
			$dc11Description->addStatement('dc:identifier', $request->url($journal->getPath(), 'article', 'view', array($article->getBestArticleId())));
		}

		// Source (journal title, issue id and pages)
		$sources = $journal->getName(null);
		$pages = $article->getPages();
		if (!empty($pages)) $pages = '; ' . $pages;
		foreach ($sources as $locale => $source) {
			if (is_a($article, 'PublishedSubmission')) {
				$sources[$locale] .= '; ' . $issue->getIssueIdentification(array(), $locale);
			}
			$sources[$locale] .=  $pages;
		}
		$this->_addLocalizedElements($dc11Description, 'dc:source', $sources);
		if ($issn = $journal->getData('onlineIssn')) {
			$dc11Description->addStatement('dc:source', $issn, METADATA_DESCRIPTION_UNKNOWN_LOCALE);
		}
		if ($issn = $journal->getData('printIssn')) {
			$dc11Description->addStatement('dc:source', $issn, METADATA_DESCRIPTION_UNKNOWN_LOCALE);
		}

		// Get galleys and supp files.
		$galleys = array();
		if (is_a($article, 'PublishedSubmission')) {
			$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
			$galleys = $articleGalleyDao->getBySubmissionId($article->getId())->toArray();
		}

		// Language
		$locales = array();
		if (is_a($article, 'PublishedSubmission')) {
			foreach ($galleys as $galley) {
				$galleyLocale = $galley->getLocale();
				if(!is_null($galleyLocale) && !in_array($galleyLocale, $locales)) {
					$locales[] = $galleyLocale;
					$dc11Description->addStatement('dc:language', AppLocale::getIso3FromLocale($galleyLocale));
				}
			}
		}
		$articleLanguage = $article->getLanguage();
		if (empty($locales) && !empty($articleLanguage)) {
			$dc11Description->addStatement('dc:language', strip_tags($articleLanguage));
		}

		// Relation
		// full text URLs
		if ($includeUrls) foreach ($galleys as $galley) {
			$relation = $request->url($journal->getPath(), 'article', 'view', array($article->getBestArticleId(), $galley->getBestGalleyId()));
			$dc11Description->addStatement('dc:relation', $relation);
			unset($relation);
		}

		// Public identifiers
		$pubIdPlugins = (array) PluginRegistry::loadCategory('pubIds', true, $journal->getId());
		foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($issue && $pubIssueId = $issue->getStoredPubId($pubIdPlugin->getPubIdType())) {
				$dc11Description->addStatement('dc:source', $pubIssueId, METADATA_DESCRIPTION_UNKNOWN_LOCALE);
				unset($pubIssueId);
			}
			if ($pubArticleId = $article->getStoredPubId($pubIdPlugin->getPubIdType())) {
				$dc11Description->addStatement('dc:identifier', $pubArticleId);
				unset($pubArticleId);
			}
			foreach ($galleys as $galley) {
				if ($pubGalleyId = $galley->getStoredPubId($pubIdPlugin->getPubIdType())) {
					$dc11Description->addStatement('dc:relation', $pubGalleyId);
					unset($pubGalleyId);
				}
			}
		}

		// Coverage
		$this->_addLocalizedElements($dc11Description, 'dc:coverage', (array) $article->getCoverage(null));

		// Rights: Add both copyright statement and license
		$copyrightHolder = $article->getLocalizedCopyrightHolder();
		$copyrightYear = $article->getCopyrightYear();
		if (!empty($copyrightHolder) && !empty($copyrightYear)) {
			$dc11Description->addStatement('dc:rights', __('submission.copyrightStatement', array('copyrightHolder' => $copyrightHolder, 'copyrightYear' => $copyrightYear)));
		}
		if ($licenseUrl = $article->getLicenseURL()) $dc11Description->addStatement('dc:rights', $licenseUrl);

		Hookregistry::call('Dc11SchemaArticleAdapter::extractMetadataFromDataObject', array($this, $article, $journal, $issue, &$dc11Description));

		return $dc11Description;
	}

	/**
	 * @see MetadataDataObjectAdapter::getDataObjectMetadataFieldNames()
	 * @param $translated boolean
	 */
	function getDataObjectMetadataFieldNames($translated = true) {
		// All DC fields are mapped.
		return array();
	}


	//
	// Private helper methods
	//
	/**
	 * Add an array of localized values to the given description.
	 * @param $description MetadataDescription
	 * @param $propertyName string
	 * @param $localizedValues array
	 */
	function _addLocalizedElements(&$description, $propertyName, $localizedValues) {
		foreach(stripAssocArray((array) $localizedValues) as $locale => $values) {
			if (is_scalar($values)) $values = array($values);
			foreach($values as $value) {
				if (!empty($value)) {
					$description->addStatement($propertyName, $value, $locale);
				}
				unset($value);
			}
		}
	}
}
