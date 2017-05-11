<?php

/**
 * @file controllers/grid/LocaleGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LocaleGridHandler
 * @ingroup controllers_grid_locale
 *
 * @brief Handle static pages grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.translator.controllers.grid.LocaleGridRow');
import('lib.pkp.classes.controllers.grid.LiteralGridCellProvider');

class LocaleGridHandler extends GridHandler {
	/** @var TranslatorPlugin The translator plugin */
	static $plugin;

	/** @var string JQuery selector for containing tab element */
	var $tabsSelector;

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
			array(ROLE_ID_SITE_ADMIN),
			array('index', 'fetchGrid', 'fetchRow', 'export', 'edit')
		);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		$this->tabsSelector = $request->getUserVar('tabsSelector');
		$this->setGridDataElements(AppLocale::getAllLocales());

		// Columns
		$cellProvider = new LiteralGridCellProvider();
		$this->addColumn(new GridColumn(
			'id',
			'common.language',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'name',
			'common.name',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider,
			array('width' => 80, 'alignment' => COLUMN_ALIGNMENT_LEFT)
		));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc Gridhandler::getRowInstance()
	 */
	function getRowInstance() {
		return new LocaleGridRow($this->tabsSelector);
	}

	//
	// Public Grid Actions
	//
	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginJavaScriptURL', self::$plugin->getJavaScriptURL($request));
		return $templateMgr->fetchJson(self::$plugin->getTemplatePath() . 'locales.tpl');
	}

	/**
	 * Display the editing options for a single locale.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function edit($args, $request) {
		$locale = $request->getUserVar('locale');
		if (!AppLocale::isLocaleValid($locale)) fatalError('Invalid locale.');

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'pluginJavaScriptURL' => self::$plugin->getJavaScriptURL($request),
			'locale' => $locale,
			'tabsSelector' => $this->tabsSelector
		));
		return $templateMgr->fetchJson(self::$plugin->getTemplatePath() . 'locale.tpl');
	}

	/**
	 * Export the locale files to the browser as a tarball.
	 * Requires tar (configured in config.inc.php) for operation.
	 * @param $args array Parameters.
	 * @param $request PKPRequest Request object.
	 */
	function export($args, $request) {
		$locale = $request->getUserVar('locale');
		if (!AppLocale::isLocaleValid($locale)) fatalError('Invalid locale.');

		// Construct the tar command
		$tarBinary = Config::getVar('cli', 'tar');
		if (empty($tarBinary) || !file_exists($tarBinary)) {
			fatalError('cli.tar binary not properly configured.');
		}
		$command = $tarBinary.' cz';
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$localeFilesList = array_merge(
			TranslatorAction::getLocaleFiles($locale),
			TranslatorAction::getMiscLocaleFiles($locale),
			array($emailTemplateDao->getMainEmailTemplateDataFilename($locale)),
			array_values(TranslatorAction::getEmailFileMap($locale))
		);

		// Include locale files (main file and plugin files)
		foreach ($localeFilesList as $file) {
			if (file_exists($file)) $command .= ' ' . escapeshellarg($file);
		}

		header('Content-Type: application/x-gtar');
		header("Content-Disposition: attachment; filename=\"$locale.tar.gz\"");
		header('Cache-Control: private'); // Workarounds for IE weirdness
		passthru($command);
	}
}

?>
