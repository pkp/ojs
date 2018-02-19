<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraExportPlugin
 * @ingroup plugins_importexport_medra
 *
 * @brief mEDRA Onix for DOI (O4DOI) export/registration plugin.
 */

import('classes.plugins.DOIPubIdExportPlugin');

// O4DOI schemas.
define('O4DOI_ISSUE_AS_WORK', 0x01);
define('O4DOI_ISSUE_AS_MANIFESTATION', 0x02);
define('O4DOI_ARTICLE_AS_WORK', 0x03);
define('O4DOI_ARTICLE_AS_MANIFESTATION', 0x04);

class MedraExportPlugin extends DOIPubIdExportPlugin {

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success && $this->getEnabled()) {
			$this->_registerTemplateResource();
		}
		return $success;
	}

	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		return 'MedraExportPlugin';
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.medra.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.medra.description');
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		return $this->getTemplateResourceName() . ':templates/';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>medra-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getIssueFilter()
	 */
	function getIssueFilter() {
		return 'issue=>medra-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getRepresentationFilter()
	 */
	function getRepresentationFilter() {
		return 'galley=>medra-xml';
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'medra';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'MedraSettingsForm';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'MedraExportDeployment';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::depositXML()
	 */
	function depositXML($objects, $context, $filename) {
		// Use a different endpoint for testing and
		// production.
		$this->import('classes.MedraWebservice');
		$endpoint = ($this->isTestMode($context) ? MEDRA_WS_ENDPOINT_DEV : MEDRA_WS_ENDPOINT);

		// Get credentials.
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		// Retrieve the XML.
		assert(is_readable($filename));
		$xml = file_get_contents($filename);
		assert($xml !== false && !empty($xml));

		// Instantiate the mEDRA web service wrapper.
		$ws = new MedraWebservice($endpoint, $username, $password);
		// Register the XML with mEDRA.
		$result = $ws->upload($xml);

		if ($result === true) {
			// Mark all objects as registered.
			foreach($objects as $object) {
				$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
				$this->saveRegisteredDoi($context, $object);
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

}

?>
