<?php

/**
 * @file controllers/grid/MiscTranslationFileGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MiscTranslationFileGridHandler
 * @ingroup controllers_grid_locale
 *
 * @brief Handle static pages grid requests.
 */

import('plugins.generic.translator.controllers.grid.BaseLocaleFileGridHandler');

class MiscTranslationFileGridHandler extends BaseLocaleFileGridHandler {
	/** @var EditableFile File. NOTE: This is only used in certain cases and may not be available */
	var $file;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		// Set the grid details.
		$this->setTitle('plugins.generic.translator.miscFiles');

		$fileList = TranslatorAction::getMiscLocaleFiles($this->locale);
		sort($fileList);
		$fileData = array();
		foreach ($fileList as $file) {
			$fileData[] = array(
				'filename' => $file,
				'status' => file_exists($file)?
					__('plugins.generic.translator.miscFile.complete'):
					__('plugins.generic.translator.miscFile.doesNotExist')
			);
		}

		$this->setGridDataElements($fileData);
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function edit($args, $request) {
		$filename = $this->_getFilename($request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'locale' => $this->locale,
			'filename' => $filename,
			'referenceContents' => file_get_contents(str_replace($this->locale, MASTER_LOCALE, $filename)),
			'fileContents' => file_exists($filename)?file_get_contents($filename):'',
		));
		return $templateMgr->fetchJson(self::$plugin->getTemplatePath() . 'editMiscFile.tpl');
	}

	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function save($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$filename = $this->_getFilename($request);
		$notificationManager = new NotificationManager();
		$user = $request->getUser();

		$contents = $this->correctCr($request->getUserVar('fileContents'));

		if (file_put_contents($filename, $contents)) {
			$notificationManager->createTrivialNotification($user->getId());
		} else {
			// Could not write the file
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('plugins.generic.translator.couldNotWriteFile', array('filename' => $filename))));
		}
		return new JSONMessage(true);
	}

	/**
	 * Get the (validated) filename for the current request.
	 * @param $request PKPRequest
	 * @return string Filename
	 */
	protected function _getFilename($request) {
		$filename = $request->getUserVar('filename');
		if (!in_array($filename, TranslatorAction::getMiscLocaleFiles($this->locale))) {
			fatalError('Invalid locale file specified!');
		}
		return $filename;
	}
}

?>
