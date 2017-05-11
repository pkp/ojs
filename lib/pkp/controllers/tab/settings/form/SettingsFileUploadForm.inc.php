<?php

/**
 * @file controllers/tab/settings/form/SettingsFileUploadForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsFileUploadForm
 * @ingroup controllers_tab_settings_form
 *
 * @brief Base class for a settings upload file form.
 */

import('lib.pkp.classes.form.Form');

class SettingsFileUploadForm extends Form {

	/** string Setting key that will be associated with the uploaded file. */
	var $_fileSettingName;

	/**
	 * Constructor.
	 * @param $template string
	 */
	function __construct($template = null) {
		if ($template == null) {
			$template = 'controllers/tab/settings/form/newFileUploadForm.tpl';
		}

		parent::__construct($template);
		$this->addCheck(new FormValidator($this, 'temporaryFileId', 'required', 'manager.website.imageFileRequired'));
	}


	//
	// Getters and setters.
	//
	/**
	 * Get the image that this form will upload a file to.
	 * @return string
	 */
	function getFileSettingName() {
		return $this->_fileSettingName;
	}

	/**
	 * Set the image that this form will upload a file to.
	 * @param $image string
	 */
	function setFileSettingName($fileSettingName) {
		$this->_fileSettingName = $fileSettingName;
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
	function fetch($request, $params=null) {
		$templateMgr = TemplateManager::getManager($request);

		if (!is_null($params)) {
			$templateMgr->assign($params);
		}
		$templateMgr->assign('fileSettingName', $this->getFileSettingName());

		return parent::fetch($request);
	}


	//
	// Public methods
	//
	/**
	 * Fecth the temporary file.
	 * @param $request Request
	 * @return TemporaryFile
	 */
	function fetchTemporaryFile($request) {
		$user = $request->getUser();

		$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFile = $temporaryFileDao->getTemporaryFile(
			$this->getData('temporaryFileId'),
			$user->getId()
		);
		return $temporaryFile;
	}

	/**
	 * Clean temporary file.
	 * @param $request Request
	 */
	function removeTemporaryFile($request) {
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFileManager->deleteFile($this->getData('temporaryFileId'), $user->getId());
	}

	/**
	 * Upload a temporary file.
	 * @param $request Request
	 */
	function uploadFile($request) {
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());

		if ($temporaryFile) return $temporaryFile->getId();

		return false;
	}
}

?>
