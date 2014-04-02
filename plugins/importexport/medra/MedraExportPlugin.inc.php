<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraExportPlugin
 * @ingroup plugins_importexport_medra
 *
 * @brief mEDRA Onix for DOI (O4DOI) export/registration plugin.
 */


if (!class_exists('DOIExportPlugin')) { // Bug #7848
	import('plugins.importexport.medra.classes.DOIExportPlugin');
}

// O4DOI schemas.
define('O4DOI_ISSUE_AS_WORK', 0x01);
define('O4DOI_ISSUE_AS_MANIFESTATION', 0x02);
define('O4DOI_ARTICLE_AS_WORK', 0x03);
define('O4DOI_ARTICLE_AS_MANIFESTATION', 0x04);

class MedraExportPlugin extends DOIExportPlugin {

	//
	// Constructor
	//
	function MedraExportPlugin() {
		parent::DOIExportPlugin();
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
	// Implement template methods from DOIExportPlugin
	//
	/**
	 * @see DOIExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'medra';
	}

	/**
	 * @see DOIExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'MedraSettingsForm';
	}

	/**
	 * @see DOIExportPlugin::generateExportFiles()
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

	/**
	 * @see DOIExportPlugin::registerDoi()
	 */
	function registerDoi(&$request, &$journal, &$objects, $file) {
		// Use a different endpoint for testing and
		// production.
		$this->import('classes.MedraWebservice');
		$endpoint = ($this->isTestMode($request) ? MEDRA_WS_ENDPOINT_DEV : MEDRA_WS_ENDPOINT);

		// Get credentials.
		$username = $this->getSetting($journal->getId(), 'username');
		$password = $this->getSetting($journal->getId(), 'password');

		// Retrieve the XML.
		assert(is_readable($file));
		$xml = file_get_contents($file);
		assert($xml !== false && !empty($xml));

		// Instantiate the mEDRA web service wrapper.
		$ws = new MedraWebservice($endpoint, $username, $password);

		// Register the XML with mEDRA.
		$result = $ws->upload($xml);

		if ($result === true) {
			// Mark all objects as registered.
			foreach($objects as $object) {
				$this->markRegistered($request, $object, MEDRA_WS_TESTPREFIX);
			}
		} else {
			// Handle errors.
			if (is_string($result)) {
				$result = array(
					array('plugins.importexport.common.register.error.mdsError', $result)
				);
			} else {
				$result = false;
			}
		}

		return $result;
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
