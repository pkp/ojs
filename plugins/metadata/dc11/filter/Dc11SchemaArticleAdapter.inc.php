<?php

/**
 * @file plugins/metadata/dc11/filter/Dc11SchemaArticleAdapter.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Dc11SchemaArticleAdapter
 * @ingroup plugins_metadata_dc11_filter
 * @see Article
 * @see PKPDc11Schema
 *
 * @brief Abstract base class for meta-data adapters that
 *  injects/extracts Dublin Core schema compliant meta-data into/from
 *  an PublishedArticle object.
 */


import('lib.pkp.classes.metadata.MetadataDataObjectAdapter');

class Dc11SchemaArticleAdapter extends MetadataDataObjectAdapter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function Dc11SchemaArticleAdapter(&$filterGroup) {
		parent::MetadataDataObjectAdapter($filterGroup);
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
	 * @param $dc11Description MetadataDescription
	 * @param $article Article
	 * @param $authorClassName string the application specific author class name
	 */
	function &injectMetadataIntoDataObject(&$dc11Description, &$article, $authorClassName) {
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

		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));

		// Retrieve data that belongs to the article.
		// FIXME: Retrieve this data from the respective entity DAOs rather than
		// from the OAIDAO once we've migrated all OAI providers to the
		// meta-data framework. We're using the OAIDAO here because it
		// contains cached entities and avoids extra database access if this
		// adapter is called from an OAI context.
		$oaiDao =& DAORegistry::getDAO('OAIDAO'); /* @var $oaiDao OAIDAO */
		$journal =& $oaiDao->getJournal($article->getJournalId());
		$section =& $oaiDao->getSection($article->getSectionId());
		if (is_a($article, 'PublishedArticle')) { /* @var $article PublishedArticle */
			$issue =& $oaiDao->getIssue($article->getIssueId());
		}

		$dc11Description =& $this->instantiateMetadataDescription();

		// Title
		$this->_addLocalizedElements($dc11Description, 'dc:title', $article->getTitle(null));

		// Creator
		$authors = $article->getAuthors();
		foreach($authors as $author) {
			$authorName = $author->getFullName(true);
			$affiliation = $author->getLocalizedAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$dc11Description->addStatement('dc:creator', $authorName);
			unset($authorName);
		}

		// Subject
		$subjects = array_merge_recursive(
				(array) $article->getDiscipline(null),
				(array) $article->getSubject(null),
				(array) $article->getSubjectClass(null));
		$this->_addLocalizedElements($dc11Description, 'dc:subject', $subjects);

		// Description
		$this->_addLocalizedElements($dc11Description, 'dc:description', $article->getAbstract(null));

		// Publisher
		$publisherInstitution = $journal->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publishers = array($journal->getPrimaryLocale() => $publisherInstitution);
		} else {
			$publishers = $journal->getTitle(null); // Default
		}
		$this->_addLocalizedElements($dc11Description, 'dc:publisher', $publishers);

		// Contributor
		$this->_addLocalizedElements($dc11Description, 'dc:contributor', $article->getSponsor(null));

		// Date
		if (is_a($article, 'PublishedArticle')) {
			$dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($issue->getDatePublished())));
		}

		// Type
		$types = $section->getIdentifyType(null);
		$types = array_merge_recursive(
			empty($types)?array(Locale::getLocale() => Locale::translate('rt.metadata.pkp.peerReviewed')):$types,
			(array) $article->getType(null)
		);
		$this->_addLocalizedElements($dc11Description, 'dc:type', $types);

		// Format
		if (is_a($article, 'PublishedArticle')) {
			$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
			$galleys =& $articleGalleyDao->getGalleysByArticle($article->getId());
			$formats = array();
			foreach ($galleys as $galley) {
				$dc11Description->addStatement('dc:format', $galley->getFileType());
			}
		}

		// Identifier: URL
		if (is_a($article, 'PublishedArticle')) {
			$dc11Description->addStatement('dc:identifier', Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId())));
		}

		// Identifier: DOI
		if($doi = $article->getDOI()) {
			$dc11Description->addStatement('dc:identifier[@xsi:type="dcterms:DOI"]', $doi);
		}

		// Source (journal title, issue id and pages)
		$sources = $journal->getTitle(null);
		$pages = $article->getPages();
		if (!empty($pages)) $pages = '; ' . $pages;
		foreach ($sources as $locale => $source) {
			$sources[$locale] .= '; ';
			if (is_a($article, 'PublishedArticle')) {
				$sources[$locale] .= $issue->getIssueIdentification();
			}
			$sources[$locale] .=  $pages;
		}
		$this->_addLocalizedElements($dc11Description, 'dc:source', $sources);

		// Language
		$dc11Description->addStatement('dc:language', strip_tags($article->getLanguage()));

		// Relation
		if (is_a($article, 'PublishedArticle')) {
			foreach ($article->getSuppFiles() as $suppFile) {
				$relation = Request::url($journal->getPath(), 'article', 'downloadSuppFile', array($article->getId(), $suppFile->getId()));
				$dc11Description->addStatement('dc:relation', $relation);
				unset($relation);
			}
		}

		// Coverage
		$coverage = array_merge_recursive(
				(array) $article->getCoverageGeo(null),
				(array) $article->getCoverageChron(null),
				(array) $article->getCoverageSample(null));
		$this->_addLocalizedElements($dc11Description, 'dc:coverage', $coverage);

		// Rights
		$this->_addLocalizedElements($dc11Description, 'dc:rights', $journal->getSetting('copyrightNotice'));

		Hookregistry::call('Dc11SchemaArticleAdapter::extractMetadataFromDataObject', array(&$this, $article, $journal, $issue, &$dc11Description));

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
				$description->addStatement($propertyName, $value, $locale);
				unset($value);
			}
		}
	}
}
?>
