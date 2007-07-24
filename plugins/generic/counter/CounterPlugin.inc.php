<?php

/**
 * @file CounterPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.counter
 * @class CounterPlugin
 *
 * COUNTER plugin; provides COUNTER-compliant statistics.
 *
 * $Id$
 */

define('COUNTER_UID_VAR', 'CounterPlugin_UID');
import('classes.plugins.GenericPlugin');

class CounterPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
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

			$this->import('LogEntryDAO');
			$logEntryDao =& new LogEntryDAO();
			DAORegistry::registerDAO('LogEntryDAO', &$logEntryDao);

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

	function displayMenuOption($hookName, $args) {
		$params =& $args[0];
		$smarty =& $args[1];
		$output =& $args[2];

		$this->addLocaleData();
		$output .= '<li>&#187; <a href="' . Request::url(null, 'counter') . '">' . Locale::translate('plugins.generic.counter') . '</a></li>';
		return false;
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

		if (($logUser = $session->getSessionVar(COUNTER_UID_VAR))=='') {
			$logUser = Core::getCurrentDate() . '_' . $session->getId();
			$session->setSessionVar(COUNTER_UID_VAR, $logUser);
		}

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

				$logEntry =& new LogEntry();
				$logEntry->setSite($site->getTitle());
				$logEntry->setJournal($journal->getTitle());
				$logEntry->setJournalUrl(Request::url(null, 'index'));
				$logEntry->setPrintIssn($journal->getSetting('printIssn'));
				$logEntry->setOnlineIssn($journal->getSetting('onlineIssn'));
				$publisher = $journal->getSetting('publisher');
				if (is_array($publisher) && isset($publisher['institution'])) $publisher = $publisher['institution'];
				$logEntry->setPublisher($publisher);
				$logEntry->setUser($logUser);
				if ($galley->isHTMLGalley()) $logEntry->setType(LOG_ENTRY_TYPE_HTML_ARTICLE);
				elseif ($galley->isPdfGalley()) $logEntry->setType(LOG_ENTRY_TYPE_PDF_ARTICLE);
				else $logEntry->setType(LOG_ENTRY_TYPE_OTHER_ARTICLE);
				$logEntry->setValue($article->getTitle());
				$logEntryDao =& DAORegistry::getDAO('LogEntryDAO');
				$logEntryDao->addEntry($logEntry);
				break;
			case 'search/searchResults.tpl':
				// Log the request as a search.
				$logEntry =& new LogEntry();
				$article = $templateManager->get_template_vars('article');
				$logEntry->setSite($site->getTitle());
				$logEntry->setJournal($journal->getTitle());
				$logEntry->setJournalUrl(Request::url(null, 'index'));
				$logEntry->setUser($logUser);
				$logEntry->setType(LOG_ENTRY_TYPE_SEARCH);
				$logEntry->setValue(Request::getUserVar('query'));
				$logEntryDao =& DAORegistry::getDAO('LogEntryDAO');
				$logEntryDao->addEntry($logEntry);
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
		if ($isEnabled) $verbs[] = array(
			'counter',
			Locale::translate('plugins.generic.counter')
		);
		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

	function manage($verb, $args) {
		$isEnabled = $this->getSetting(0, 'enabled');
		$this->addLocaleData();
		switch ($verb) {
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
