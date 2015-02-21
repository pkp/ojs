<?php

/**
 * @defgroup plugins_metadata_mods34_filter
 */

/**
 * @file plugins/metadata/mods34/filter/Mods34SchemaArticleAdapter.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34SchemaArticleAdapter
 * @ingroup plugins_metadata_mods34_filter
 * @see Article
 * @see PublishedArticle
 * @see Mods34Schema
 *
 * @brief Class that inject/extract MODS schema compliant meta-data
 *  into/from an Article or PublishedArticle object.
 */

import('lib.pkp.plugins.metadata.mods34.filter.Mods34SchemaSubmissionAdapter');

class Mods34SchemaArticleAdapter extends Mods34SchemaSubmissionAdapter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function Mods34SchemaArticleAdapter(&$filterGroup) {
		// Configure the submission adapter
		parent::Mods34SchemaSubmissionAdapter($filterGroup);
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
	 * @param $mods34Description MetadataDescription
	 * @param $article Article
	 */
	function &injectMetadataIntoDataObject(&$mods34Description, &$article) {
		assert(is_a($article, 'Article'));
		$article =& parent::injectMetadataIntoDataObject($mods34Description, $article, 'classes.article.Author');

		// ...
		// FIXME: Go through MODS schema and see what context-specific
		// information needs to be added, e.g. from Article, PublishedArticle
		// Issue, Journal, journal settings or site settings.

		return $article;
	}

	/**
	 * @see MetadataDataObjectAdapter::extractMetadataFromDataObject()
	 * @param $article Article
	 */
	function &extractMetadataFromDataObject(&$article) {
		assert(is_a($article, 'Article'));

		// Extract meta-data from the submission.
		$mods34Description =& parent::extractMetadataFromDataObject($article, 'aut');

		// ...
		// FIXME: Go through MODS schema and see what context-specific
		// information needs to be added, e.g. from Article, PublishedArticle
		// Issue, Journal, journal settings or site settings.

		return $mods34Description;
	}
}
?>
