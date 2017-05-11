<?php

/**
 * @file controllers/listbuilder/LocaleFileListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LocaleFileListbuilderHandler
 * @ingroup controllers_listbuilder_content_navigation
 *
 * @brief Class for managing footer links.
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

class LocaleFileListbuilderHandler extends ListbuilderHandler {
	/** @var TranslatorPlugin The translator plugin */
	static $plugin;

	/** @var string Locale */
	var $locale;

	/** @var string Filename */
	var $filename;

	/**
	 * Set the translator plugin.
	 * @param $plugin StaticPagesPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_SITE_ADMIN,
			array('fetch', 'fetchRow', 'save', 'fetchOptions')
		);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc SetupListbuilderHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		$context = $request->getContext();

		$this->setTitle('plugins.generic.translator.localeFileContents');

		// Get and validate the locale and filename parameters
		$this->locale = $request->getUserVar('locale');
		if (!AppLocale::isLocaleValid($this->locale)) fatalError('Invalid locale.');
		$this->filename = $request->getUserVar('filename');
		if (!in_array($this->filename, TranslatorAction::getLocaleFiles($this->locale))) {
			fatalError('Invalid locale file specified!');
		}

		// Basic configuration
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_TEXT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('localeKeys');

		self::$plugin->import('controllers.listbuilder.LocaleFileListbuilderGridCellProvider');

		$cellProvider = new LocaleFileListbuilderGridCellProvider($this->locale);
		// Key column
		$this->addColumn(new ListbuilderGridColumn(
			$this, 'key', 'plugins.generic.translator.localeKey',
			null,
			self::$plugin->getTemplatePath() . 'localeFileKeyGridCell.tpl',
			$cellProvider,
			array('tabIndex' => 1)
		));

		// Value column (custom template displays English text)
		$this->addColumn(new ListbuilderGridColumn(
			$this, 'value', 'plugins.generic.translator.localeKeyValue',
			null,
			self::$plugin->getTemplatePath() . 'localeFileValueGridCell.tpl',
			$cellProvider,
			array('tabIndex' => 2, 'width' => 70, 'alignment' => COLUMN_ALIGNMENT_LEFT)
		));
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request) {
		import('lib.pkp.classes.file.EditableLocaleFile');
		$referenceLocaleContents = EditableLocaleFile::load(str_replace($this->locale, MASTER_LOCALE, $this->filename));
		$localeContents = file_exists($this->filename)?EditableLocaleFile::load($this->filename):array();

		$returner = array();
		foreach ($referenceLocaleContents as $key => $value) {
			$returner[$key][MASTER_LOCALE] = $value;
		}
		foreach ($localeContents as $key => $value) {
			$returner[$key][$this->locale] = $value;
		}

		return $returner;
	}

	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(
			parent::getRequestArgs(),
			array(
				'locale' => $this->locale,
				'filename' => $this->filename,
			)
		);
	}

	/**
	 * @copydoc GridHandler::getRowDataElement
	 */
	function getRowDataElement($request, &$rowId) {
		// fallback on the parent if a rowId is found
		if (!empty($rowId)) {
			return parent::getRowDataElement($request, $rowId);
		}
		// A new row is being bounced back to the user.
		// Supply a new ID from the specified key.
		$newRowId = $request->getUserVar('newRowId');
		$rowId = $newRowId['key'];

		// Send the value specified back to the user for formatting.
		return $newRowId['value'];
	}
}
?>
