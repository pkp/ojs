<?php

/**
 * @file classes/plugins/DOIPubIdExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdExportPlugin
 * @ingroup plugins
 *
 * @brief Basis class for DOI XML metadata export plugins
 */

import('classes.plugins.PubObjectsExportPlugin');

// Configuration errors.
define('DOI_EXPORT_CONFIG_ERROR_DOIPREFIX', 0x01);
define('DOI_EXPORT_CONFIG_ERROR_SETTINGS', 0x02);

// The name of the setting used to save the registered DOI.
define('DOI_EXPORT_REGISTERED_DOI', 'registeredDoi');

abstract class DOIPubIdExportPlugin extends PubObjectsExportPlugin {

	/**
	 * Constructor
	 */
	function DOIPubIdExportPlugin() {
		parent::PubObjectsExportPlugin();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		}
		return $success;
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		parent::display($args, $request);
		$context = $request->getContext();
		switch (array_shift($args)) {
			case 'index':
			case '':
				// Check for configuration errors:
				$configurationErrors = array();
				// 1) missing DOI prefix
				$doiPrefix = $exportArticles = $exportIssues = null;
				$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
				if (isset($pubIdPlugins['doipubidplugin'])) {
					$doiPlugin = $pubIdPlugins['doipubidplugin'];
					$doiPrefix = $doiPlugin->getSetting($context->getId(), $doiPlugin->getPrefixFieldName());
					$exportArticles = $doiPlugin->getSetting($context->getId(), 'enableSubmissionDoi');
					$exportIssues = $doiPlugin->getSetting($context->getId(), 'enableIssueDoi');
					$exportRepresentations = $doiPlugin->getSetting($context->getId(), 'enableRepresentationDoi');
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
				// Add link actions
				$actions = $this->getExportActions($context);
				$actionNames = array_intersect_key($this->getExportActionNames(), array_flip($actions));
				import('lib.pkp.classes.linkAction.request.NullAction');
				$linkActions = array();
				foreach ($actionNames as $action => $actionName) {
					$linkActions[] = new LinkAction($action, new NullAction(), $actionName);
				}
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign(array(
					'plugin' => $this,
					'actionNames' => $actionNames,
					'configurationErrors' => $configurationErrors,
					'exportArticles' => $exportArticles,
					'exportIssues' => $exportIssues,
					'exportRepresentations' => $exportRepresentations,
				));
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
		}
	}

	/**
	 * @copydoc PubObjectsExportPlugin::executeExportAction()
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());
		if ($request->getUserVar(EXPORT_ACTION_DEPOSIT)) {
			assert($filter != null);
			// Get the XML
			$exportXml = $this->exportXML($objects, $filter, $context);
			// Write the XML to a file.
			// export file name example: crossref/20160723-160036-articles-1.xml
			$exportFileName = $this->getExportFileName($objectsFileNamePart, $context);
			file_put_contents($exportFileName, $exportXml);
			// Deposit the XML file.
			$result = $this->depositXML($objects, $context, $exportFileName);
			// send notifications
			if ($result === true) {
				$this->_sendNotification(
					$request->getUser(),
					$this->getDepositSuccessNotificationMessageKey(),
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
		parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart);
	}

	/**
	 * Get pub ID type
	 * @return string
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * Get pub ID display type
	 * @return string
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * Get the locale key used in the notification for
	 * the successful deposit.
	 */
	function getDepositSuccessNotificationMessageKey() {
		return 'plugins.importexport.common.register.success';
	}

	/**
	 * Deposit XML document.
	 * This must be implemented in the subclasses, if the action is supported.
	 * @param $objects mixed Array of or single published article, issue or galley
	 * @param $context Context
	 * @param $filename Export XML filename
	 * @return boolean Whether the XML document has been registered
	 */
	abstract function depositXML($objects, $context, $filename);

	/**
	 * Mark selected submissions or issues as registered.
	 * @param $context Context
	 * @param $objects array Array of published articles, issues or galleys
	 */
	function markRegistered($context, $objects) {
		foreach ($objects as $object) {
			$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_MARKEDREGISTERED);
			$this->saveRegisteredDoi($context, $object);
		}
	}

	/**
	 * Saving object's DOI to the object's
	 * "registeredDoi" setting.
	 * We prefix the setting with the plugin's
	 * id so that we do not get name clashes
	 * when several DOI registration plug-ins
	 * are active at the same time.
	 * @param $context Context
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $testPrefix string
	 */
	function saveRegisteredDoi($context, $object, $testPrefix = '10.1234') {
		$registeredDoi = $object->getStoredPubId('doi');
		assert(!empty($registeredDoi));
		if ($this->isTestMode($context)) {
			$registeredDoi = PKPString::regexp_replace('#^[^/]+/#', $testPrefix . '/', $registeredDoi);
		}
		$object->setData($this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI, $registeredDoi);
		$this->updateObject($object);
	}

	/**
	 * Hook callback that returns the
	 * "registeredDoi" setting's name prefixed with
	 * the plug-in's id to avoid name collisions.
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $args array
	 */
	function getAdditionalFieldNames($hookName, $args) {
		parent::getAdditionalFieldNames($hookName, $args);
		$additionalFields =& $args[1];
		assert(is_array($additionalFields));
		$additionalFields[] = $this->getPluginSettingsPrefix() . '::' . DOI_EXPORT_REGISTERED_DOI;
	}

	/**
	 * @copydoc AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
		return false;
	}

	/**
	 * Retrieve all unregistered articles.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredArticles($context) {
		// Retrieve all published articles that have not yet been registered.
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getExportable(
			$context->getId(),
			$this->getPubIdType(),
			null,
			null,
			null,
			$this->getPluginSettingsPrefix(). '::' . DOI_EXPORT_REGISTERED_DOI,
			null,
			null
		);
		return $articles->toArray();
	}

	/**
	 * Retrieve all unregistered issues.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredIssues($context) {
		// Retrieve all issues that have not yet been registered.
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issuesFactory = $issueDao->getExportable(
			$context->getId(),
			$this->getPubIdType(),
			$this->getPluginSettingsPrefix(). '::' . DOI_EXPORT_REGISTERED_DOI,
			null,
			null
		);
		$issues = $issuesFactory->toArray();
		// Cache issues.
		$cache = $this->getCache();
		foreach ($issues as $issue) {
			$cache->add($issue, null);
			unset($issue);
		}
		return $issues;
	}

	/**
	 * Retrieve all unregistered articles.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredGalleys($context) {
		// Retrieve all galleys that have not yet been registered.
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = $galleyDao->getExportable(
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
	 * Instantiate the settings form.
	 * @param $context Context
	 * @return CrossRefSettingsForm
	 */
	function _instantiateSettingsForm($context) {
		$settingsFormClassName = $this->getSettingsFormClassName();
		$this->import('classes.form.' . $settingsFormClassName);
		$settingsForm = new $settingsFormClassName($this, $context->getId());
		return $settingsForm;
	}


}

?>
