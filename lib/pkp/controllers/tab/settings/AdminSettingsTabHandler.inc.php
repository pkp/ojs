<?php

/**
 * @file controllers/tab/settings/AdminSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on administration settings pages.
 */

// Import the base Handler.
import('lib.pkp.classes.controllers.tab.settings.SettingsTabHandler');

class AdminSettingsTabHandler extends SettingsTabHandler {

	/**
	 * Constructor
	 * @param $additionalTabs array Optional additional ('tabname' => 'class/template name') mappings
	 */
	function __construct($additionalTabs = array()) {
		$role = array(ROLE_ID_SITE_ADMIN);

		$this->addRoleAssignment(ROLE_ID_MANAGER,
			array(
				'showFileUploadForm',
				'uploadFile',
				'saveFile',
				'deleteFile',
				'fetchFile'
			)
		);

		parent::__construct($role);
		$this->setPageTabs(array_merge($additionalTabs, array(
			'siteSetup' => 'lib.pkp.controllers.tab.settings.siteSetup.form.SiteSetupForm',
			'languages' => 'controllers/tab/admin/languages/languages.tpl',
			'plugins' => 'controllers/tab/admin/plugins/sitePlugins.tpl',
		)));
	}


	//
	// Extended methods from SettingsTabHandler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load grid-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_ADMIN,
			LOCALE_COMPONENT_APP_ADMIN,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_APP_MANAGER
		);
	}


	//
	// Public methods.
	//
	/**
	 * Show the upload image form.
	 * @param $request Request
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	function showFileUploadForm($args, $request) {
		$fileUploadForm = $this->_getFileUploadForm($request);
		$fileUploadForm->initData($request);

		return new JSONMessage(true, $fileUploadForm->fetch($request));
	}

	/**
	 * Upload a new file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function uploadFile($args, $request) {
		$fileUploadForm =& $this->_getFileUploadForm($request);
		$temporaryFileId = $fileUploadForm->uploadFile($request);

		if ($temporaryFileId !== false) {
			$json = new JSONMessage();
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFileId
			));
			return $json;
		} else {
			return new JSONMessage(false, __('common.uploadFailed'));
		}
	}

	/**
	 * Save an uploaded file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveFile($args, $request) {
		$fileUploadForm = $this->_getFileUploadForm($request);
		$fileUploadForm->readInputData();

		if ($fileUploadForm->validate()) {
			if ($fileUploadForm->execute($request)) {
				// Generate a JSON message with an event
				$settingName = $request->getUserVar('fileSettingName');
				return DAO::getDataChangedEvent($settingName);
			}
		}
		return new JSONMessage(false, __('common.invalidFileType'));
	}

	/**
	 * Deletes a context image.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteFile($args, $request) {
		$settingName = $request->getUserVar('fileSettingName');

		$tabForm = $this->getTabForm();
		$tabForm->initData($request);

		if ($request->checkCSRF() && $tabForm->deleteFile($settingName, $request)) {
			return DAO::getDataChangedEvent($settingName);
		}
		return new JSONMessage(false);
	}

	/**
	 * Fetch a file that have been uploaded.
	 *
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function fetchFile($args, $request) {
		// Get the setting name.
		$settingName = $args['settingName'];

		// Try to fetch the file.
		$tabForm = $this->getTabForm();
		$tabForm->initData($request);

		$renderedElement = $tabForm->renderFileView($settingName, $request);

		$json = new JSONMessage();
		if ($renderedElement == false) {
			$json->setAdditionalAttributes(array('noData' => $settingName));
		} else {
			$json->setElementId($settingName);
			$json->setContent($renderedElement);
		}
		return $json;
	}


	//
	// Private helper methods.
	//
	/**
	 * Returns a file upload form based on the uploaded file type.
	 * @param $request Request
	 * @return Form
	 */
	function &_getFileUploadForm($request) {
		$settingName = $request->getUserVar('fileSettingName');
		$fileType = $request->getUserVar('fileType');

		switch ($fileType) {
			case 'image':
				import('lib.pkp.controllers.tab.settings.siteSetup.form.NewSiteImageFileForm');
				$fileUploadForm = new NewSiteImageFileForm($settingName);
				break;
			case 'css':
				import('lib.pkp.controllers.tab.settings.siteSetup.form.NewSiteCssFileForm');
				$fileUploadForm = new NewSiteCssFileForm($settingName);
				break;
			default:
				assert(false);
				break;
		}

		return $fileUploadForm;
	}
}

?>
