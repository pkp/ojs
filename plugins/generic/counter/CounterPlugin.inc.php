<?php

/**
 * @file plugins/generic/counter/CounterPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterPlugin
 * @ingroup plugins_generic_counter
 *
 * @brief COUNTER plugin; provides COUNTER statistics.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class CounterPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {

			HookRegistry::register ('Templates::Admin::Index::AdminFunctions', array($this, 'displayMenuOption'));
			HookRegistry::register ('Templates::Manager::Index::ManagementPages', array($this, 'displayMenuOption'));
			HookRegistry::register ('LoadHandler', array($this, 'handleRequest'));
			HookRegistry::register ('TemplateManager::display', array($this, 'logRequest'));
			HookRegistry::register ('FileManager::downloadFile', array($this, 'logRequestInline'));

			$this->import('CounterReportDAO');
			$counterReportDao = new CounterReportDAO();
			DAORegistry::registerDAO('CounterReportDAO', $counterReportDao);
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.counter');
	}

	function getDescription() {
		return __('plugins.generic.counter.description');
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

		$request =& $this->getRequest();
		$output .= '<li>&#187; <a href="' . $request->url(null, 'counter') . '">' . __('plugins.generic.counter') . '</a></li>';
		return false;
	}

	/**
	 * Log a request for an lineable galley (e.g. text file).
	 */
	function logRequestInline($hookName, $args) {
		$request = $this->getRequest();
		$journal = $request->getJournal();
		if (!$journal || $request->getRequestedPage() != 'article' || $request->getRequestedOp() != 'view') return false;

		$counterReportDao = DAORegistry::getDAO('CounterReportDAO');
		$counterReportDao->incrementCount($journal->getId(), (int) strftime('%Y'), (int) strftime('%m'), false, false);
	}

	/**
	 * Log the request.
	 * This follows a convoluted execution path in order to obtain the
	 * page title *after* the template has been displayed, even though
	 * the hook is called before execution.
	 */
	function logRequest($hookName, $args) {
		$templateManager = $args[0];
		$template =& $args[1];
		$request = $this->getRequest();

		$site = $request->getSite();
		$journal = $request->getJournal();
		$session =& $request->getSession();

		if (!$journal) return false;

		/* NOTE: Project COUNTER has a list of robots on their site
		   unfortunately not in a very accessible format:
		   http://www.projectcounter.org/r3/r3_K.doc
		*/
		if ($request->isBot()) return false;

		// TODO: consider the effect of LOCKSS on COUNTER recording
		assert(false); // Template names no longer apply (e.g. interstitial)

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

				$lastRequestGap = time() - $session->getSessionVar('lastRequest');
				// if last request was less than 10 seconds ago then return without recording this view
				if ( $lastRequestGap < 10 ) return false;
				// if last request was less than 30 seconds ago AND is PDF then return without recording this view
				if ( $galley->isPdfGalley() && ($lastRequestGap < 30) ) return false;
				$session->setSessionVar('lastRequest', time());

				$counterReportDao = DAORegistry::getDAO('CounterReportDAO');
				$counterReportDao->incrementCount($article->getJournalId(), (int) strftime('%Y'), (int) strftime('%m'), $galley->isPdfGalley(), $galley->isHTMLGalley());
				break;
		}

		return false;
	}

	function handleRequest($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];

		// If the request is for the log analyzer itself, handle it.
		if ($page === 'counter' && in_array($op, array('index', 'reportXML', 'sushiXML', 'report'))) {
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
		$verbs = parent::getManagementVerbs();

		if ($this->getEnabled()) {
			$verbs[] = array('counter', __('plugins.generic.counter'));

			$this->import('CounterReportDAO');
			$counterReportDao = new CounterReportDAO();
			DAORegistry::registerDAO('CounterReportDAO', $counterReportDao);
		}
		return $verbs;
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();
		switch ($verb) {
			case 'counter':
				$request->redirect(null, 'counter');
				return false;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}
}

?>
