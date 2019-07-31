<?php

/**
 * @file plugins/generic/metadataExport/metadataExportFormats/bibtex/BibtexMetadataExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BibtexMetadataExportPlugin
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_bibtex
 *
 * @brief BibTeX metadata export format plugin
 */

import('plugins.generic.metadataExport.MetadataExportPlugin');

class BibtexMetadataExportPlugin extends MetadataExportPlugin {
	
	/**
	 * @copydoc MetadataExportPlugin::getName()
	 */
	function getName() {
		return 'BibtexMetadataExportPlugin';
	}

	/**
	 * @copydoc MetadataExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.metadataExportFormats.bibtex.displayName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getMetadataExportFormatName()
	 */
	function getMetadataExportFormatName() {
		return __('plugins.metadataExportFormats.bibtex.metadataExportFormatName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.metadataExportFormats.bibtex.description');
	}
	
	/**
	 * @copydoc MetadataExportPlugin::getFileExtension()
	 */
	function getFileExtension() {
		return 'bib';
	}

	/**
	 * @copydoc MetadataExportPlugin::getFileContent()
	 * This function uses the BibtexCitationPlugin.
	 */
	function getFileContent($journal, $articles) {
		import('plugins.citationFormats.bibtex.BibtexCitationPlugin');
		$bibtexCitationPlugin = new BibtexCitationPlugin();
		$bibtexCitationPlugin->register('citationFormats', 'plugins/citationFormats/bibtex');

		$data = '';
		foreach($articles as $article) {
			$issue = $this->issueDao->getIssueById($article->getIssueId(), $journal->getId());
		
			$metadata = strip_tags($bibtexCitationPlugin->fetchCitation($article, $issue, $journal));
			$metadata = preg_replace('/[\r\n]+[\s\t]*[\r\n]+/', "\n", $metadata);
			$data .= $metadata;	
		}
		return trim($data);
	}
}
?>