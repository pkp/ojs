<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraExportPlugin
 * @ingroup plugins_importexport_medra
 *
 * @brief mEDRA Onix for DOI (O4DOI) export/registration plugin.
 */


import('plugins.importexport.medra.classes.DoiExportPlugin');

// O4DOI schemas.
define('O4DOI_ISSUE_AS_WORK', 0x01);
define('O4DOI_ISSUE_AS_MANIFESTATION', 0x02);
define('O4DOI_ARTICLE_AS_WORK', 0x03);
define('O4DOI_ARTICLE_AS_MANIFESTATION', 0x04);

class MedraExportPlugin extends DoiExportPlugin {

	//
	// Constructor
	//
	function MedraExportPlugin() {
		parent::DoiExportPlugin();
	}


	//
	// Implement template methods from ImportExportPlugin
	//
	/**
	 * @see ImportExportPlugin::getName()
	 */
	function getName() {
		return 'MedraExportPlugin';
	}

	/**
	 * @see ImportExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.medra.displayName');
	}

	/**
	 * @see ImportExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.medra.description');
	}


	//
	// Implement template methods from DoiExportPlugin
	//
	/**
	 * @see DoiExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'medra';
	}

	/**
	 * @see DoiExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'MedraSettingsForm';
	}

	/**
	 * @see DoiExportPlugin::generateExportFiles()
	 */
	function generateExportFiles(&$request, $exportType, &$objects, $targetPath, &$journal, &$errors) {
		assert(count($objects) >= 1);

		// Identify the O4DOI schema to export.
		$exportIssuesAs = $this->getSetting($journal->getId(), 'exportIssuesAs');
		$schema = $this->_identifyO4DOISchema($exportType, $journal, $exportIssuesAs);
		assert(!is_null($schema));

		// Create the XML DOM and document.
		$this->import('classes.O4DOIExportDom');
		$dom = new O4DOIExportDom($request, $this, $schema, $journal, $this->getCache(), $exportIssuesAs);
		$doc =& $dom->generate($objects);
		if ($doc === false) {
			$errors =& $dom->getErrors();
			return false;
		}

		// Write the result to the target file.
		$exportFileName = $this->getTargetFileName($targetPath, $exportType);
		file_put_contents($exportFileName, XMLCustomWriter::getXML($doc));
		$generatedFiles = array($exportFileName => &$objects);
		return $generatedFiles;
	}

	//
	// Private helper methods
	//
	/**
	 * Determine the O4DOI export schema.
	 *
	 * @param $exportType integer One of the DOI_EXPORT_* constants.
	 * @param $journal Journal
	 * @param $exportIssuesAs Whether issues are exported as work
	 *  or as manifestation. One of the O4DOI_* schema constants.
	 *
	 * @return integer One of the O4DOI_* schema constants.
	 */
	function _identifyO4DOISchema($exportType, &$journal, $exportIssuesAs) {
		switch ($exportType) {
			case DOI_EXPORT_ISSUES:
				assert ($exportIssuesAs === O4DOI_ISSUE_AS_WORK || $exportIssuesAs === O4DOI_ISSUE_AS_MANIFESTATION);
				return $exportIssuesAs;

			case DOI_EXPORT_ARTICLES:
				return O4DOI_ARTICLE_AS_WORK;

			case DOI_EXPORT_GALLEYS:
				return O4DOI_ARTICLE_AS_MANIFESTATION;
		}

		return null;
	}
}

?>
