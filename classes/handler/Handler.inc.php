<?php

/**
 * @file classes/handler/Handler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Handler
 * @ingroup handler
 *
 * @brief Base request handler application class
 */


import('lib.pkp.classes.handler.PKPHandler');

class Handler extends PKPHandler {
	function Handler() {
		parent::PKPHandler();
	}

	/**
	 * Get the iterator of working contexts.
	 * @param $request PKPRequest
	 * @return ItemIterator
	 */
	function getWorkingContexts($request) {
		// For installation process
		if (defined('SESSION_DISABLE_INIT') || !Config::getVar('general', 'installed')) {
			return null;
		}

		// Check for multiple journals.
		$journalDao = DAORegistry::getDAO('JournalDAO');

		$user = $request->getUser();
		if (is_a($user, 'User')) {
			return $journalDao->getAll();
		} else {
			return $journalDao->getAll(true); // Enabled only
		}
	}

	/**
	 * Returns a "best-guess" journal, based in the request data, if
	 * a request needs to have one in its context but may be in a site-level
	 * context as specified in the URL.
	 * @param $request Request
	 * @param $journalsCount int Optional reference to receive journals count
	 * @return mixed Either a Journal or null if none could be determined.
	 */
	function getTargetContext($request, &$journalsCount = null) {
		// Get the requested path.
		$router = $request->getRouter();
		$requestedPath = $router->getRequestedContextPath($request);
		$journal = null;

		if ($requestedPath === 'index' || $requestedPath === '') {
			// No journal requested. Check how many journals the site has.
			$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journals = $journalDao->getAll();
			$journalsCount = $journals->getCount();
			$journal = null;
			if ($journalsCount === 1) {
				// Return the unique journal.
				$journal = $journals->next();
			}
			if (!$journal && $journalsCount > 1) {
				// Get the site redirect.
				$journal = $this->getSiteRedirectContext($request);
			}
		} else {
			$contextCount = null;
			// Return the requested journal.
			$journal = $router->getContext($request);
		}
		if (is_a($journal, 'Journal')) {
			return $journal;
		}
		return null;
	}

	/**
	 * Return the journal that is configured in site redirect setting.
	 * @param $request Request
	 * @return mixed Either Journal or null
	 */
	function getSiteRedirectContext($request) {
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$site = $request->getSite();
		$journal = null;
		if ($site) {
			if($site->getRedirect()) {
				$journal = $journalDao->getById($site->getRedirect());
			}
		}
		return $journal;
	}
}

?>
