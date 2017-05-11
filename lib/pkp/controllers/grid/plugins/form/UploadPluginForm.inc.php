<?php

/**
 * @file controllers/grid/plugins/form/UploadPluginForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UploadPluginForm
 * @ingroup controllers_grid_plugins_form
 *
 * @brief Form to upload a plugin file.
 */

// Import the base Form.
import('lib.pkp.classes.form.Form');

import('lib.pkp.classes.plugins.PluginHelper');
import('lib.pkp.classes.file.FileManager');

class UploadPluginForm extends Form {

	/** @var String PLUGIN_ACTION_... */
	var $_function;


	/**
	 * Constructor.
	 * @param $function string PLUGIN_ACTION_...
	 */
	function __construct($function) {
		parent::__construct('controllers/grid/plugins/form/uploadPluginForm.tpl');

		$this->_function = $function;

		$this->addCheck(new FormValidator($this, 'temporaryFileId', 'required', 'manager.plugins.uploadFailed'));
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('temporaryFileId'));
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'function' => $this->_function,
			'category' => $request->getUserVar('category'),
			'plugin' => $request->getUserVar('plugin'),
		));

		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		parent::execute($request);

		// Retrieve the temporary file.
		$user = $request->getUser();
		$temporaryFileId = $this->getData('temporaryFileId');
		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

		$pluginHelper = new PluginHelper();
		$errorMsg = null;
		$pluginDir = $pluginHelper->extractPlugin($temporaryFile->getFilePath(), $temporaryFile->getOriginalFileName(), $errorMsg);
		$notificationMgr = new NotificationManager();
		if ($pluginDir) {
			if ($this->_function == PLUGIN_ACTION_UPLOAD) {
				$pluginVersion = $pluginHelper->installPlugin($pluginDir, $errorMsg);
				if ($pluginVersion) $notificationMgr->createTrivialNotification(
					$user->getId(),
					NOTIFICATION_TYPE_SUCCESS,
					array('contents' =>
						__('manager.plugins.installSuccessful', array('versionNumber' => $pluginVersion->getVersionString(false))))
				);
			} else if ($this->_function == PLUGIN_ACTION_UPGRADE) {
				$plugin = PluginRegistry::getPlugin($request->getUserVar('category'), $request->getUserVar('plugin'));
				$pluginVersion = $pluginHelper->upgradePlugin(
					$request->getUserVar('category'),
					basename($plugin->getPluginPath()),
					$pluginDir,
					$errorMsg
				);
				if ($pluginVersion) {
					$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('manager.plugins.upgradeSuccessful', array('versionString' => $pluginVersion->getVersionString(false)))));
				}
			}
		} else {
			$errorMsg = __('manager.plugins.invalidPluginArchive');
		}

		if ($errorMsg) {
			$notificationMgr->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => $errorMsg));
			return false;
		}

		return true;
	}
}

?>
