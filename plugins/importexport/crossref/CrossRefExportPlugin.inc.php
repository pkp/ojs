<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportPlugin
 * @ingroup plugins_importexport_crossref
 *
 * @brief CrossRef/MEDLINE XML metadata export plugin
 */

import('classes.plugins.DOIPubIdExportPlugin');

// The status of the Crossref DOI.
// any, notDeposited, and markedRegistered are reserved
define('CROSSREF_STATUS_SUBMITTED', 'submitted');
define('CROSSREF_STATUS_FAILED', 'failed');
define('CROSSREF_STATUS_COMPLETED', 'completed');
define('CROSSREF_STATUS_REGISTERED', 'found');

define('CROSSREF_EXPORT_ACTION_CHECKSTATUS', 'checkStatus');

define('CROSSREF_API_DEPOSIT_OK', 303);
define('CROSSREF_API_RESPONSE_OK', 200);

define('CROSSREF_API_URL', 'https://api.crossref.org/deposits');
//TESTING
define('CROSSREF_API_URL_DEV', 'https://api.crossref.org/deposits?test=true');
define('CROSSREF_WORKS_API', 'http://api.crossref.org/works/');

// The name of the settings used to save the registered DOI and the URL with the deposit status.
define('CROSSREF_DEPOSIT_STATUS', 'depositStatus');


class CrossRefExportPlugin extends DOIPubIdExportPlugin {

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'CrossRefExportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.crossref.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.crossref.description');
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>crossref-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getStatusNames()
	 */
	function getStatusNames() {
		return array_merge(parent::getStatusNames(), array(
			CROSSREF_STATUS_SUBMITTED => __('plugins.importexport.crossref.status.submitted'),
			CROSSREF_STATUS_COMPLETED => __('plugins.importexport.crossref.status.completed'),
			CROSSREF_STATUS_REGISTERED => __('plugins.importexport.crossref.status.registered'),
			CROSSREF_STATUS_FAILED => __('plugins.importexport.crossref.status.failed'),
			EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.crossref.status.markedRegistered'),
		));
	}

	/**
	 * Provide the link to more status information only if the DOI deposit failed
	 *
	 * @copydoc PubObjectsExportPlugin::getStatusActions()
	 */
	function getStatusActions($pubObject) {
		return array(
			CROSSREF_STATUS_FAILED => 'https://api.crossref.org'.$pubObject->getData($this->getDepositStatusUrlSettingName()),
		);
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportActions()
	 */
	function getExportActions($context) {
		$actions = array(EXPORT_ACTION_EXPORT, EXPORT_ACTION_MARKREGISTERED, );
		if ($this->getSetting($context->getId(), 'username') && $this->getSetting($context->getId(), 'password')) {
			array_unshift($actions, EXPORT_ACTION_DEPOSIT, CROSSREF_EXPORT_ACTION_CHECKSTATUS);
		}
		return $actions;
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportActionNames()
	 */
	function getExportActionNames() {
		return array(
			EXPORT_ACTION_DEPOSIT => __('plugins.importexport.crossref.action.register'),
			CROSSREF_EXPORT_ACTION_CHECKSTATUS => __('plugins.importexport.crossref.action.checkStatus'),
			EXPORT_ACTION_EXPORT => __('plugins.importexport.crossref.action.export'),
			EXPORT_ACTION_MARKREGISTERED => __('plugins.importexport.crossref.action.markRegistered'),
		);
	}

	/**
	 * Hook callback that returns the deposit setting's names,
	 * to consider them by article or issue update.
	 *
	 * @copydoc PubObjectsExportPlugin::getAdditionalFieldNames()
	 */
	function getAdditionalFieldNames($hookName, $args) {
		parent::getAdditionalFieldNames($hookName, $args);
		$additionalFields =& $args[1];
		assert(is_array($additionalFields));
		$additionalFields[] = $this->getDepositStatusUrlSettingName();
		$additionalFields[] = $this->getDepositBatchIdSettingName();
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'crossref';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'CrossRefSettingsForm';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'CrossrefExportDeployment';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::executeExportAction()
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());
		if ($request->getUserVar(CROSSREF_EXPORT_ACTION_CHECKSTATUS)) {
			$this->checkStatus($objects, $context);
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} else {
			parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation);
		}
	}

	/**
	 * Check statuses for selected publication objects.
	 * @param $objects array Array of published articles, issues or galleys
	 * @param $context Context
	 */
	function checkStatus($objects, $context) {
		foreach ($objects as $object) {
			$this->updateDepositStatus($context, $object);
		}
	}

	/**
	 * @copydoc PubObjectsExportPlugin::depositXML()
	 */
	function depositXML($objects, $context, $filename) {
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HEADER, 1);
		curl_setopt($curlCh, CURLOPT_BINARYTRANSFER, true);
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');

		// Use a different endpoint for testing and
		// production.
		$endpoint = ($this->isTestMode($context) ? CROSSREF_API_URL_DEV : CROSSREF_API_URL);
		curl_setopt($curlCh, CURLOPT_URL, $endpoint);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

		// Transmit XML data.
		assert(is_readable($filename));
		$fh = fopen($filename, 'rb');

		$httpheaders = array();
		$httpheaders[] = 'Content-Type: application/vnd.crossref.deposit+xml';
		$httpheaders[] = 'Content-Length: ' . filesize($filename);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $httpheaders);
		curl_setopt($curlCh, CURLOPT_INFILE, $fh);
		curl_setopt($curlCh, CURLOPT_INFILESIZE, filesize($filename));

		$response = curl_exec($curlCh);

		if ($response === false) {
			$result = array(array('plugins.importexport.crossref.register.error.mdsError', 'No response from server.'));
		} elseif ( $status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE) != CROSSREF_API_DEPOSIT_OK ) {
			$result = array(array('plugins.importexport.crossref.register.error.mdsError', "$status - $response"));
		} else {
			// Deposit was received
			$result = true;
			foreach ($objects as $object) {
				// update the status and save the URL of the last deposit
				// (note: the registration could be done outside the system, so it is better to always update the URL together with the status)
				$this->updateDepositStatus($context, $object);
			}
		}
		curl_close($curlCh);
		return $result;
	}

	/**
	 * Check the CrossRef APIs, if deposits and registration have been successful
	 * @param $context Context
	 * @param $object The object getting deposited
	 */
	function updateDepositStatus($context, $object) {
		assert(is_a($object, 'PublishedArticle') or is_a($object, 'Issue'));
		// Prepare HTTP session.
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");
		$doi = urlencode($object->getStoredPubId('doi'));
		$params = 'filter=doi:' . $doi ;

		// Use a different endpoint for testing and
		// production.
		$endpoint = ($this->isTestMode($context) ? CROSSREF_API_URL_DEV : CROSSREF_API_URL);
		curl_setopt(
			$curlCh,
			CURLOPT_URL,
			$endpoint . (strpos($endpoint,'?')===false?'?':'&') . $params
		);
		// try to fetch from the new API
		$response = curl_exec($curlCh);

		// try the new API with the filter completed (should only return successes)
		if ($response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == CROSSREF_API_RESPONSE_OK)  {
			$response = json_decode($response);
			$pastDeposits = array();
			foreach ($response->message->items as $item) {
				$pastDeposits[strtotime($item->{'submitted-at'})] = array('status' => $item->status, 'batch-id' => $item->{'batch-id'});
			}
			// if there have been past attempts, save the most recent one's status for display to user
			if (count($pastDeposits) > 0) {
				$lastDeposit = $pastDeposits[max(array_keys($pastDeposits))];
				$lastStatus = $lastDeposit['status'];
				$lastBatchId = $lastDeposit['batch-id'];
				// If batch-id changed
				if ($object->getData($this->getDepositStatusUrlSettingName()) != '/deposits/'.$lastBatchId) {
					// Set the depositStausUrl
					$object->setData($this->getDepositStatusUrlSettingName(), '/deposits/'.$lastBatchId);
				}
				if ($lastStatus == CROSSREF_STATUS_COMPLETED) {
					// check if the DOI is active (there is a delay between a deposit completing successfully and a DOI being 'ready').
					curl_setopt(
						$curlCh,
						CURLOPT_URL,
						CROSSREF_WORKS_API . $doi
					);
					$response = curl_exec($curlCh);
					if ($response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == CROSSREF_API_RESPONSE_OK) {
						// set the status, because we will need to check it for the automatic registration
						$object->setData($this->getDepositStatusSettingName(), CROSSREF_STATUS_REGISTERED);
						// Save the DOI -- the object will be updated
						$this->saveRegisteredDoi($context, $object);
						return true;
					}
				}
				// If status changed
				if ($object->getData($this->getDepositStatusSettingName()) != $lastStatus) {
					// set the status, because we will need to check it for the automatic registration
					$object->setData($this->getDepositStatusSettingName(), $lastStatus);
				}
				if ($object->getData($this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI)) {
					// apparently there was a new registreation i.e. update
					// remove the setting defining the article as registered, for the article to be considered for automatic status updates
					$object->setData($this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI, null);
				}
				// Update the object
				$this->updateObject($object);
			}
		}
		curl_close($curlCh);
		return false;
	}

	/**
	 * Get deposit status/batch ID URL setting name.
	 * @return string
	 */
	function getDepositStatusUrlSettingName() {
		return $this->getPluginSettingsPrefix().'::statusUrl';
	}

	/**
	 * Get deposit batch ID setting name.
	 * @return string
	 */
	function getDepositBatchIdSettingName() {
		return $this->getPluginSettingsPrefix().'::batchId';
	}

	/**
	 * @copydoc DOIExportPlugin::getDepositSuccessNotificationMessageKey()
	 */
	function getDepositSuccessNotificationMessageKey() {
		return 'plugins.importexport.common.register.success';
	}

}


