<?php

/**
 * @file controllers/tab/settings/appearance/form/NewContextImageFileForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NewContextImageFileForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form for upload an image.
 */

import('lib.pkp.controllers.tab.settings.form.SettingsFileUploadForm');

class NewContextImageFileForm extends SettingsFileUploadForm {

	/**
	 * Constructor.
	 * @param $imageSettingName string
	 */
	function __construct($imageSettingName) {
		parent::__construct('controllers/tab/settings/form/newImageFileUploadForm.tpl');
		$this->setFileSettingName($imageSettingName);
	}


	//
	// Extend methods from SettingsFileUploadForm.
	//
	/**
	 * @copydoc SettingsFileUploadForm::fetch()
	 */
	function fetch($request) {
		$params = array('fileType' => 'image');
		return parent::fetch($request, $params);
	}


	//
	// Extend methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('imageAltText');
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData($request) {
		$context = $request->getContext();
		$fileSettingName = $this->getFileSettingName();

		$image = $context->getSetting($fileSettingName);
		$imageAltText = array();

		$supportedLocales = AppLocale::getSupportedLocales();
		foreach ($supportedLocales as $key => $locale) {
			if (!isset($image[$key]['altText'])) continue;
			$imageAltText[$key] = $image[$key]['altText'];
		}

		$this->setData('imageAltText', $imageAltText);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('imageAltText'));

		parent::readInputData();
	}

	/**
	 * Save the new image file.
	 * @param $request Request.
	 */
	function execute($request) {
		$temporaryFile = $this->fetchTemporaryFile($request);

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if (is_a($temporaryFile, 'TemporaryFile')) {
			$type = $temporaryFile->getFileType();
			$extension = $publicFileManager->getImageExtension($type);
			if (!$extension) {
				return false;
			}
			$locale = AppLocale::getLocale();
			$context = $request->getContext();

			$uploadName = $this->getFileSettingName() . '_' . $locale . $extension;
			if($publicFileManager->copyContextFile($context->getAssocType(), $context->getId(), $temporaryFile->getFilePath(), $uploadName)) {

				// Get image dimensions
				$filePath = $publicFileManager->getContextFilesPath($context->getAssocType(), $context->getId());
				list($width, $height) = getimagesize($filePath . '/' . $uploadName);

				$value = $context->getSetting($this->getFileSettingName());
				$imageAltText = $this->getData('imageAltText');

				$value[$locale] = array(
					'name' => $temporaryFile->getOriginalFileName(),
					'uploadName' => $uploadName,
					'width' => $width,
					'height' => $height,
					'dateUploaded' => Core::getCurrentDate(),
					'altText' => $imageAltText[$locale]
				);

				$settingsDao = $context->getSettingsDAO();
				$settingsDao->updateSetting($context->getId(), $this->getFileSettingName(), $value, 'object', true);

				// Clean up the temporary file.
				$this->removeTemporaryFile($request);

				return true;
			}
		}
		return false;
	}
}

?>
