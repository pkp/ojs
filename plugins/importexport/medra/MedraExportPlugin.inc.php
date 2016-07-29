<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	 * Constructor
	 */
	function MedraExportPlugin() {
		parent::DOIPubIdExportPlugin();
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
	 * @copydoc DOIExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>medra-xml';
	}

	/**
	 * @copydoc DOIExportPlugin::getIssueFilter()
	 */
	function getIssueFilter() {
		return 'issue=>medra-xml';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getPluginSettingsPrefix()
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
	 * @copydoc DOIPubIdExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'MedraExportDeployment';
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		$context = $request->getContext();
		switch (current($args)) {
			case 'exportRepresentations':
				$selectedRepresentations = (array) $request->getUserVar('selectedRepresentations');
				if (!empty($selectedRepresentations)) {
					$objects = $this->_getArticleGalleys($selectedRepresentations, $context);
					$filter = 'galley=>medra-xml';
					$tab = (string) $request->getUserVar('tab');
					$objectsFileNamePart = 'galleys';
				}
				// Execute export action
				$this->executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart);
			default:
				parent::display($args, $request);
		}
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::depositXML()
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
				$object->setData($this->getDepositStatusSettingName(), DOI_EXPORT_STATUS_REGISTERED);
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

	/**
	 * Retrieve all unregistered articles.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredGalleys($context) {
		// Retrieve all galleys that have not yet been registered.
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = $galleyDao->getByPubIdType(
			$this->getPubIdType(),
			$context?$context->getId():null,
			null,
			null,
			null,
			$this->getPluginSettingsPrefix(). '::' . DOI_EXPORT_REGISTERED_DOI,
			null,
			null
		);
		return $galleys->toArray();
	}


	/**
	 * Get article galleys from gallley IDs.
	 * @param $galleyIds array
	 * @param $context Context
	 * @return array
	 */
	function _getArticleGalleys($galleyIds, $context) {
		$galleys = array();
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		foreach ($galleyIds as $galleyId) {
			$articleGalley = $articleGalleyDao->getById($galleyId, null, $context->getId());
			if ($articleGalley) $galleys[] = $articleGalley;
		}
		return $galleys;
	}

}

?>
