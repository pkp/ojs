<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		$this->import('classes.MedraWebservice');
		// Use a different endpoint for testing and production.
		// New endpoint: use a different endpoint if the user selects the checkbox to deposit also in Crossref.
		$crEnabled = false;
		if ($this->_request->getUserVar('crEnabled') == 'on') $crEnabled = true;

		$endpoint = ($this->isTestMode($context) ? ($crEnabled ? MEDRA2CR_WS_ENDPOINT_DEV : MEDRA_WS_ENDPOINT_DEV) : ($crEnabled ? MEDRA2CR_WS_ENDPOINT : MEDRA_WS_ENDPOINT));

		// Get credentials.
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		// Retrieve the XML.
		assert(is_readable($filename));
		$xml = file_get_contents($filename);
		assert($xml !== false && !empty($xml));

		// Get the current user locale to get the Crossref service validation error messages in that language
		// Currently only supported: eng, ita
		$language = 'eng';
		$supportedLanguages = array('eng', 'ita');
		$user3LetterLang = AppLocale::get3LetterIsoFromLocale(AppLocale::getLocale());
		if (in_array($user3LetterLang, $supportedLanguages)) {
			$language = $user3LetterLang;
		}

		// Instantiate the mEDRA web service wrapper.
		$ws = new MedraWebservice($endpoint, $username, $password);
		// Register the XML with mEDRA (upload) or also with Crossref (deposit)
		$result = $crEnabled ? $ws->deposit($xml, $language) : $ws->upload($xml);

		if ($result === true) {
			// Mark all objects as registered.
			foreach($objects as $object) {
				$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
				$this->saveRegisteredDoi($context, $object);
			}
		} else {
			// Handle errors.
			if (is_string($result)) {
				$doc = new DOMDocument();
				$doc->loadXML($result);
				$statusCode = $doc->getElementsByTagName("statusCode");
				if ($statusCode->length > 0 && $statusCode->item(0)->textContent == 'FAILED'){
					$errNo = $doc->getElementsByTagName('errorsNumber')->item(0)->textContent;
					$errNodeList = $doc->getElementsByTagName('error');
					$errors = array();
					foreach($errNodeList as $errNode) {
						$error = array();
						if($errNode->childNodes->length) {
							foreach($errNode->childNodes as $errChildNode) {
								$error[$errChildNode->nodeName] = $errChildNode->nodeValue;
							}
						}
						$errors[] = $error;
					}
					$templateMgr = TemplateManager::getManager($this->_request);
					$templateMgr->assign([
						'errNo' => $errNo,
						'errors' => $errors,
						'xml' => $xml,
					]);
					$templateMgr->display($this->getTemplateResource('crDepositErrors.tpl'));
					exit();
				}
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


