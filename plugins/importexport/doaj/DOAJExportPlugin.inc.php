<?php

/**
 * @file plugins/importexport/doaj/DOAJExportPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief DOAJ export plugin
 */

import('classes.plugins.PubObjectsExportPlugin');

define('DOAJ_XSD_URL', 'https://www.doaj.org/schemas/doajArticles.xsd');

define('DOAJ_API_DEPOSIT_OK', 201);

define('DOAJ_API_URL', 'https://doaj.org/api/v2/');
define('DOAJ_API_URL_DEV', 'https://testdoaj.cottagelabs.com/api/v2/');
define('DOAJ_API_OPERATION', 'articles');

class DOAJExportPlugin extends PubObjectsExportPlugin {
	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'DOAJExportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.doaj.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.doaj.description');
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		parent::display($args, $request);
		switch (array_shift($args)) {
			case 'index':
			case '':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->display($this->getTemplateResource('index.tpl'));
				break;
		}
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'doaj';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>doaj-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportActions()
	 */
	function getExportActions($context) {
		$actions = array(EXPORT_ACTION_EXPORT, EXPORT_ACTION_MARKREGISTERED );
		if ($this->getSetting($context->getId(), 'apiKey')) {
			array_unshift($actions, EXPORT_ACTION_DEPOSIT);
		}
		return $actions;
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'DOAJExportDeployment';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'DOAJSettingsForm';
	}

	/**
	 * @see PubObjectsExportPlugin::depositXML()
	 * @param $objects Submission
	 * @param $context Context
	 * @param $jsonString string Export JSON string
	 * @return boolean Whether the JSON string has been registered
	 */
	function depositXML($objects, $context, $jsonString) {

		import('lib.pkp.classes.helpers.PKPCurlHelper');
		$curlCh = PKPCurlHelper::getCurlObject();

		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $jsonString);

		$endpoint = ($this->isTestMode($context) ? DOAJ_API_URL_DEV : DOAJ_API_URL);
		$apiKey = $this->getSetting($context->getId(), 'apiKey');
		$params = 'api_key=' . $apiKey;

		curl_setopt(
			$curlCh,
			CURLOPT_URL,
			$endpoint . DOAJ_API_OPERATION . (strpos($endpoint,'?')===false?'?':'&') . $params
		);

		$response = curl_exec($curlCh);

		if ($response === false) {
			$result = array(array('plugins.importexport.doaj.register.error.mdsError', 'No response from server.'));
		} elseif ( $status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE) != DOAJ_API_DEPOSIT_OK ) {
			$result = array(array('plugins.importexport.doaj.register.error.mdsError', "$status - $response"));
		} else {
			// Deposit was received
			$result = true;
			// set the status
			$objects->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
			// Update the object
			$this->updateObject($objects);
		}
		curl_close($curlCh);
		return $result;
	}

	/**
	 * @copydoc PubObjectsExportPlugin::executeExportAction()
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());
		if ($request->getUserVar(EXPORT_ACTION_DEPOSIT)) {
			assert($filter != null);
			// Set filter for JSON
			$filter = 'article=>doaj-json';
			$resultErrors = array();
			foreach ($objects as $object) {
				// Get the JSON
				$exportJson = $this->exportJSON($object, $filter, $context);
				// Deposit the JSON
				$result = $this->depositXML($object, $context, $exportJson);
				if (is_array($result)) {
					$resultErrors[] = $result;
				}
			}
			// send notifications
			if (empty($resultErrors)) {
				$this->_sendNotification(
					$request->getUser(),
					$this->getDepositSuccessNotificationMessageKey(),
					NOTIFICATION_TYPE_SUCCESS
				);
			} else {
				foreach($resultErrors as $errors) {
					foreach ($errors as $error) {
						assert(is_array($error) && count($error) >= 1);
						$this->_sendNotification(
							$request->getUser(),
							$error[0],
							NOTIFICATION_TYPE_ERROR,
							(isset($error[1]) ? $error[1] : null)
						);
					}
				}
			}
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} else {
			return parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation);
		}
	}

	/**
	 * Get the JSON for selected objects.
	 * @param $object Submission
	 * @param $filter string
	 * @param $context Context
	 * @return string JSON variable.
	 */
	function exportJSON($object, $filter, $context) {
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$exportFilters = $filterDao->getObjectsByGroup($filter);
		assert(count($exportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($exportFilters);
		$exportDeployment = $this->_instantiateExportDeployment($context);
		$exportFilter->setDeployment($exportDeployment);
		return $exportFilter->execute($object, true);
	}
}

