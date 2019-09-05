<?php

/**
 * @file plugins/metadata/mods34/filter/Mods34SchemaArticleAdapter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34SchemaArticleAdapter
 * @ingroup plugins_metadata_mods34_filter
 * @see Article
 * @see Submission
 * @see Mods34Schema
 *
 * @brief Class that inject/extract MODS schema compliant meta-data
 *  into/from an Article or Submission object.
 */

import('lib.pkp.plugins.metadata.mods34.filter.Mods34SchemaSubmissionAdapter');

class Mods34SchemaArticleAdapter extends Mods34SchemaSubmissionAdapter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		// Configure the submission adapter
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getClassName()
	 */
	function getClassName() {
		return 'plugins.metadata.mods34.filter.Mods34SchemaArticleAdapter';
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
		assert(is_a($targetDataObject, 'Submission'));
		$article = parent::injectMetadataIntoDataObject($metadataDescription, $targetDataObject);

		// ...
		// FIXME: Go through MODS schema and see what context-specific
		// information needs to be added, e.g. from Article, Submission
		// Issue, Journal, journal settings or site settings.

		return $article;
	}

	/**
	 * @see MetadataDataObjectAdapter::extractMetadataFromDataObject()
	 * @param $article Article
	 */
	function &extractMetadataFromDataObject(&$article) {
		assert(is_a($article, 'Submission'));

		// Extract meta-data from the submission.
		$mods34Description =& parent::extractMetadataFromDataObject($article);

		// ...
		// FIXME: Go through MODS schema and see what context-specific
		// information needs to be added, e.g. from Article, Submission
		// Issue, Journal, journal settings or site settings.

		return $mods34Description;
	}
}

