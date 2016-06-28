<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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

define('CROSSREF_API_DEPOSIT_OK', 303);
define('CROSSREF_API_RESPONSE_OK', 200);

//define('CROSSREF_API_URL', 'https://api.crossref.org/deposits');
//TESTING
define('CROSSREF_API_URL', 'https://api.crossref.org/deposits?test=true');
define('CROSSREF_WORKS_API', 'http://api.crossref.org/works/');

// The name of the settings used to save the registered DOI and the URL with the deposit status.
define('CROSSREF_DEPOSIT_STATUS', 'depositStatus');


class CrossRefExportPlugin extends DOIPubIdExportPlugin {
	/**
	 * Constructor
	 */
	function CrossRefExportPlugin() {
		parent::DOIPubIdExportPlugin();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->import('CrossrefExportDeployment');
		return $success;
	}

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
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		parent::display($args, $request);

		$templateMgr->assign('plugin', $this);

		switch (array_shift($args)) {
			case 'index':
			case '':
				// Check for configuration errors:
				$configurationErrors = array();
				// 1) missing DOI prefix
				$doiPrefix = null;
				$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
				if (isset($pubIdPlugins['doipubidplugin'])) {
					$doiPlugin = $pubIdPlugins['doipubidplugin'];
					$doiPrefix = $doiPlugin->getSetting($context->getId(), $doiPlugin->getPrefixFieldName());
					$exportArticles = $doiPlugin->getSetting($context->getId(), 'enableSubmissionDoi');
					$exportIssues = $doiPlugin->getSetting($context->getId(), 'enableIssueDoi');
				}
				if (empty($doiPrefix)) {
					$configurationErrors[] = DOI_EXPORT_CONFIG_ERROR_DOIPREFIX;
				}

				// 2) missing plugin settings
				$form = $this->_instantiateSettingsForm($context);
				foreach($form->getFormFields() as $fieldName => $fieldType) {
					if ($form->isOptional($fieldName)) continue;
					$pluginSetting = $this->getSetting($context->getId(), $fieldName);
					if (empty($pluginSetting)) {
						$configurationErrors[] = DOI_EXPORT_CONFIG_ERROR_SETTINGS;
						break;
					}
				}
				// Actions
				$actions = array(DOI_EXPORT_ACTION_MARKREGISTERED, DOI_EXPORT_ACTION_EXPORT);
				if ($this->getSetting($context->getId(), 'username') && $this->getSetting($context->getId(), 'password')) {
					array_push($actions, DOI_EXPORT_ACTION_DEPOSIT, DOI_EXPORT_ACTION_CHECKSTATUS);
				}
				$actionNames = array_intersect_key($this->getActionNames(), array_flip($actions));
				$templateMgr->assign('actions', $actionNames);
				$templateMgr->assign('configurationErrors', $configurationErrors);
				$templateMgr->assign('exportArticles', $exportArticles);
				$templateMgr->assign('exportIssues', $exportIssues);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
			case 'exportSubmissions':
			case 'exportIssues':
				$selectedSubmissions = (array) $request->getUserVar('selectedSubmissions');
				$selectedIssues = (array) $request->getUserVar('selectedIssues');

				if (empty($selectedSubmissions) && empty($selectedIssues)) {
					echo __('plugins.importexport.common.error.noObjectsSelected');
					break;
				}
				if (!empty($selectedSubmissions)) {
					$objects = $this->_getPublishedArticles($selectedSubmissions, $context);
					$filter = 'article=>crossref-xml';
					$tab = 'exportSubmissions-tab';
				} elseif (!empty($selectedIssues)) {
					$objects = $this->_getPublishedIssues($selectedIssues, $context);
					$filter = 'issue=>crossref-xml';
					$tab = 'exportIssues-tab';
				}
				$path = array('plugin', $this->getName());

				if ($request->getUserVar(DOI_EXPORT_ACTION_EXPORT) || $request->getUserVar(DOI_EXPORT_ACTION_DEPOSIT)) {
					// Get the XML
					$exportXml = $this->exportXML($objects, $filter, $context, $request->getUser());
					// Write the XML to a file.
					$exportFileName = $this->getExportPath() . date('Ymd-His') . '.xml';
					file_put_contents($exportFileName, $exportXml);

					if ($request->getUserVar(DOI_EXPORT_ACTION_EXPORT)) {
						// Return XML to the user
						header('Content-Type: application/xml');
						header('Cache-Control: private');
						header('Content-Disposition: attachment; filename="' . basename($exportFileName) . '"');
						readfile($exportFileName);
					} else { //deposit
						// Deposit the XML file.
						$result = $this->depositXML($request, $objects, $context, $exportFileName);
						// send notifications
						if ($result === true) {
							$this->_sendNotification(
								$request->getUser(),
								'plugins.importexport.crossref.register.success',
								NOTIFICATION_TYPE_SUCCESS
							);
						} else {
							if (is_array($result)) {
								foreach($result as $error) {
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
						// Remove all temporary files.
						$this->cleanTmpfile($exportFileName);
						// redirect back to the right tab
						$request->redirect(null, null, null, $path, null, $tab);
					}
				} elseif ($request->getUserVar(DOI_EXPORT_ACTION_MARKREGISTERED)) {
						$this->markRegistered($request, $objects);
						// redirect back to the right tab
						$request->redirect(null, null, null, $path, null, $tab);
				} elseif ($request->getUserVar(DOI_EXPORT_ACTION_CHECKSTATUS)) {
						$this->checkStatus($request, $objects, $context);
						// redirect back to the right tab
						$request->redirect(null, null, null, $path, null, $tab);
				} else {
					$dispatcher = $request->getDispatcher();
					$dispatcher->handle404();
				}
		}
	}

	/**
	 * @copydoc DOIExportPlugin::getStatusNames()
	 */
	function getStatusNames() {
		return array_merge(parent::getStatusNames(), array(
			CROSSREF_STATUS_SUBMITTED => __('plugins.importexport.crossref.status.submitted'),
			CROSSREF_STATUS_COMPLETED => __('plugins.importexport.crossref.status.completed'),
			CROSSREF_STATUS_REGISTERED => __('plugins.importexport.crossref.status.registered'),
			CROSSREF_STATUS_FAILED => __('plugins.importexport.crossref.status.failed'),
		));
	}

	/**
	 * @copydoc DOIExportPlugin::getStatusActions()
	 *
	 * Provide the link to more status information only if the DOI deposit failed
	 */
	function getStatusActions($pubObject) {
		return array(
			CROSSREF_STATUS_FAILED => 'https://api.crossref.org'.$pubObject->getData($this->getDepositStatusUrlSettingName()),
		);
	}

	/**
	 * @copydoc DOIExportPlugin::getActionNames()
	 */
	function getActionNames() {
		return array_merge(parent::getActionNames(), array(DOI_EXPORT_ACTION_EXPORT => __('plugins.importexport.crossref.export')));
	}

	/**
	 * Hook callback that returns the deposit setting's names,
	 * to consider them by article or issue update.
	 *
	 * @copydoc DOIPubIdExportPlugin::getAdditionalFieldNames()
	 */
	function getAdditionalFieldNames($hookName, $args) {
		parent::getAdditionalFieldNames($hookName, $args);
		assert(count($args) == 2);
		$dao =& $args[0];
		$returner =& $args[1];
		assert(is_array($returner));
		$returner[] = $this->getDepositStatusSettingName();
		$returner[] = $this->getDepositStatusUrlSettingName();
		$returner[] = $this->getDepositBatchIdSettingName();
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'crossref';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'CrossRefSettingsForm';
	}

	/**
	 * Get the XML for selected submissions or issues.
	 * @param $objects array Array of published articles or issues
	 * @param $filter string
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied DOIs.
	 */
	function exportXML($objects, $filter, $context, $user) {
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup($filter);
		assert(count($nativeExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment(new CrossrefExportDeployment($context, $this, $user));
		libxml_use_internal_errors(true);
		$exportXml = $exportFilter->execute($objects, true);
		$xml = $exportXml->saveXml();
		$errors = array_filter(libxml_get_errors(), create_function('$a', 'return $a->level == LIBXML_ERR_ERROR ||  $a->level == LIBXML_ERR_FATAL;'));
		if (!empty($errors)) {
			$this->displayXMLValidationErrors($errors, $xml);
			fatalError(__('plugins.importexport.common.error.validation'));
		}
		return $xml;
	}

	/**
	 * Check statuses for selected submissions or issues.
	 * @param $request Request
	 * @param $objects array Array of articles or issues
	 * @param $context Context
	 */
	function checkStatus($request, $objects, $context) {
		foreach ($objects as $object) {
			$this->updateDepositStatus($request, $context, $object);
		}
	}

	/**
	 * Deposit submissions or issues DOIs.
	 * @param $request Request
	 * @param $objects array
	 * @param $context Context
	 * @param $filename Export XML filename
	 * @return boolean Weather the DOI has been registered/found
	 */
	function depositXML($request, $objects, $context, $filename) {
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
		curl_setopt($curlCh, CURLOPT_URL, CROSSREF_API_URL);
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
				$this->updateDepositStatus($request, $context, $object);
			}
		}
		curl_close($curlCh);
		return $result;

	}

	/**
	 * Check the CrossRef APIs, if deposits and registration have been successful
	 * @param $request Request
	 * @param $context Journal The journal associated with the deposit
	 * @param $object Article or Issue The article or issue getting deposited
	 */
	function updateDepositStatus($request, $context, $object) {
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

		curl_setopt(
			$curlCh,
			CURLOPT_URL,
			CROSSREF_API_URL . (strpos(CROSSREF_API_URL,'?')===false?'?':'&') . $params
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
						$this->saveRegisteredDoi($request, $object);
						return true;
					}
				}
				// If status changed
				if ($object->getData($this->getDepositStatusSettingName()) != $lastStatus) {
					// set the status, because we will need to check it for the automatic registration
					$object->setData($this->getDepositStatusSettingName(), $lastStatus);
				}
				if ($object->getData($this->getPluginId() . '::' . DOI_EXPORT_REGISTERED_DOI)) {
					// apparently there was a new registreation i.e. update
					// remove the setting defining the article as registered, for the article to be considered for automatic status updates
					$object->setData($this->getPluginId() . '::' . DOI_EXPORT_REGISTERED_DOI, null);
				}
				// Update the object
				$this->updateObject($object);
			}
		}
		curl_close($curlCh);
		return false;
	}

	/**
	 * Get deposit status setting name.
	 * @return string
	 */
	function getDepositStatusSettingName() {
		return $this->getPluginId().'::status';
	}

	/**
	 * Get deposit status/batch ID URL setting name.
	 * @return string
	 */
	function getDepositStatusUrlSettingName() {
		return $this->getPluginId().'::statusUrl';
	}

	/**
	 * Get deposit batch ID setting name.
	 * @return string
	 */
	function getDepositBatchIdSettingName() {
		return $this->getPluginId().'::batchId';
	}

	/**
	 * Get published articles from submission IDs.
	 * @param $submissionIds array
	 * @param $context Context
	 * @return array
	 */
	function _getPublishedArticles($submissionIds, $context) {
		$publishedArticles = array();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($submissionIds as $submissionId) {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submissionId, $context->getId());
			if ($publishedArticle) $publishedArticles[] = $publishedArticle;
		}
		return $publishedArticles;
	}

	/**
	 * Get published issues from issue IDs.
	 * @param $issueIds array
	 * @param $context Context
	 * @return array
	 */
	function _getPublishedIssues($issueIds, $context) {
		$publishedIssues = array();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		foreach ($issueIds as $issueId) {
			$publishedIssue = $issueDao->getById($issueId, $context->getId());
			if ($publishedIssue) $publishedIssues[] = $publishedIssue;
		}
		return $publishedIssues;
	}

	/**
	 * Add a notification.
	 * @param $user User
	 * @param $message string An i18n key.
	 * @param $notificationType integer One of the NOTIFICATION_TYPE_* constants.
	 * @param $param string An additional parameter for the message.
	 */
	function _sendNotification($user, $message, $notificationType, $param = null) {
		static $notificationManager = null;
		if (is_null($notificationManager)) {
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
		}
		if (!is_null($param)) {
			$params = array('param' => $param);
		} else {
			$params = null;
		}
		$notificationManager->createTrivialNotification(
			$user->getId(),
			$notificationType,
			array('contents' => __($message, $params))
		);
	}


}

?>
