<?php

/**
 * @file plugins/generic/metadataExport/metadataExportFormats/ris/RisMetadataExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RisMetadataExportPlugin
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_ris
 *
 * @brief RIS metadata export format plugin
 */

import('plugins.generic.metadataExport.MetadataExportPlugin');

class RisMetadataExportPlugin extends MetadataExportPlugin {

	/**
	 * @copydoc MetadataExportPlugin::getName()
	 */
	function getName() {
		return 'RisMetadataExportPlugin';
	}

	/**
	 * @copydoc MetadataExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.metadataExportFormats.ris.displayName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getMetadataExportFormatName()
	 */
	function getMetadataExportFormatName() {
		return __('plugins.metadataExportFormats.ris.metadataExportFormatName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.metadataExportFormats.ris.description');
	}
	
	/**
	 * @copydoc MetadataExportPlugin::getFileExtension()
	 */
	function getFileExtension() {
		return 'ris';
	}

	/**
	 * @copydoc MetadataExportPlugin::getFileContent()
	 * This function uses the ProCiteCitationPlugin (= RIS).
	 */
	function getFileContent($journal, $articles) {
		import('plugins.citationFormats.procite.ProCiteCitationPlugin');
		$proCiteCitationPlugin = new ProCiteCitationPlugin();
		$proCiteCitationPlugin->register('citationFormats', 'plugins/citationFormats/proCite');
		
		$data = '';
		foreach($articles as $article) {
			$issue = $this->issueDao->getIssueById($article->getIssueId(), $journal->getId());
			
			$metadata = $proCiteCitationPlugin->fetchCitation($article, $issue, $journal);
			$data .= $metadata . "\n";
		}
		return trim($data);
	}
}
?>