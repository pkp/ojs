<?php

/**
 * @file CounterPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterPlugin
 * @ingroup plugins_generic_counter
 *
 * @brief COUNTER plugin; provides COUNTER statistics.
 */

// $Id$


import('classes.plugins.GenericPlugin');

class CounterPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$isEnabled = $this->getSetting(0, 'enabled');
		$success = parent::register($category, $path);
		if ($success && $isEnabled === true) {

			HookRegistry::register ('Templates::Admin::Index::AdminFunctions', array(&$this, 'displayMenuOption'));
			HookRegistry::register ('Templates::Manager::Index::ManagementPages', array(&$this, 'displayMenuOption'));
			HookRegistry::register ('LoadHandler', array(&$this, 'handleRequest'));
			HookRegistry::register ('TemplateManager::display', array(&$this, 'logRequest'));
			HookRegistry::register ('FileManager::downloadFile', array(&$this, 'logRequestInline'));

			$this->import('CounterReportDAO');
			$counterReportDao =& new CounterReportDAO();
			DAORegistry::registerDAO('CounterReportDAO', $counterReportDao);
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'CounterPlugin';
	}

	function getDisplayName() {
		$this->addLocaleData();
		return Locale::translate('plugins.generic.counter');
	}

	function getDescription() {
		$this->addLocaleData();
		return Locale::translate('plugins.generic.counter.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	function displayMenuOption($hookName, $args) {
		if (!Validation::isSiteAdmin()) return false;

		$params =& $args[0];
		$smarty =& $args[1];
		$output =& $args[2];

		$this->addLocaleData();
		$output .= '<li>&#187; <a href="' . Request::url(null, 'counter') . '">' . Locale::translate('plugins.generic.counter') . '</a></li>';
		return false;
	}

	/**
	 * Log a request for an lineable galley (e.g. text file).
	 */
	function logRequestInline($hookName, $args) {
		$journal =& Request::getJournal();
		if (!$journal || Request::getRequestedPage() != 'article' || Request::getRequestedOp() != 'view') return false;
		
		$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
		$counterReportDao->incrementCount($journal->getJournalId(), (int) strftime('%Y'), ((int) strftime('%m')) - 1, false, false);
	}

	/**
	 * Log the request.
	 * This follows a convoluted execution path in order to obtain the
	 * page title *after* the template has been displayed, even though
	 * the hook is called before execution.
	 */
	function logRequest($hookName, $args) {
		$templateManager =& $args[0];
		$template =& $args[1];

		$site =& Request::getSite();
		$journal =& Request::getJournal();
		$session =& Request::getSession();

		if (!$journal) return false;

		switch ($template) {
			case 'article/article.tpl':
			case 'article/interstitial.tpl':
			case 'article/pdfInterstitial.tpl':
				// Log the request as an article view.
				$article = $templateManager->get_template_vars('article');
				$galley = $templateManager->get_template_vars('galley');

				// If no galley exists, this is an abstract
				// view -- don't include it. (FIXME?)
				if (!$galley) return false;

				$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
				$counterReportDao->incrementCount($article->getJournalId(), (int) strftime('%Y'), ((int) strftime('%m')) - 1, $galley->isPdfGalley(), $galley->isHTMLGalley());
				break;
		}

		return false;
	}

	function handleRequest($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];

		// If the request is for the log analyzer itself, handle it.
		if ($page === 'counter') {
			$this->addLocaleData();
			$this->import('CounterHandler');
			Registry::set('plugin', $this);
			define('HANDLER_CLASS', 'CounterHandler');
			return true;
		}

		return false;
	}

	function isSitePlugin() {
		return true;
	}

	function getManagementVerbs() {
		$this->addLocaleData();
		$isEnabled = $this->getSetting(0, 'enabled');

		$verbs = array();

		// Non-site admin managers cannot manage Counter plugin.
		if (!Validation::isSiteAdmin()) return $verbs;

		if ($isEnabled) {
			$verbs[] = array(
				'counter',
				Locale::translate('plugins.generic.counter')
			);
			$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
			if (file_exists($counterReportDao->getOldLogFilename())) {
				$verbs[] = array(
					'migrate',
					Locale::translate('plugins.generic.counter.migrate')
				);
			}
		}
		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

	function manage($verb, $args) {

		// Non-site admin managers cannot manage Counter plugin.
		if (!Validation::isSiteAdmin()) return false;

		$isEnabled = $this->getSetting(0, 'enabled');
		$this->addLocaleData();
		switch ($verb) {
			case 'migrate':
				$counterReportDao =& DAORegistry::getDAO('CounterReportDAO');
				$counterReportDao->upgradeFromLogFile();
				Request::redirect('index', 'counter');
				break;
			case 'enable':
				$this->updateSetting(0, 'enabled', true);
				break;
			case 'disable':
				$this->updateSetting(0, 'enabled', false);
				break;
			case 'counter':
				if ($isEnabled) Request::redirect(null, 'counter');
		}
		return false;
	}
}

?>
