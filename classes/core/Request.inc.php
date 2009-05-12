<?php

/**
 * @file classes/core/Request.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup core
 *
 * @brief Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<journal_id>/<page_name>/<operation_name>/<arguments...>
 * <journal_id> is assumed to be "index" for top-level site requests.
 */

// $Id$


import('core.PKPRequest');

class Request extends PKPRequest {
	/**
	 * Redirect to the specified page within OJS. Shorthand for a common call to Request::redirect(Request::url(...)).
	 * @param $journalPath string The path of the journal to redirect to.
	 * @param $page string The name of the op to redirect to.
	 * @param $op string optional The name of the op to redirect to.
	 * @param $path mixed string or array containing path info for redirect.
	 * @param $params array Map of name => value pairs for additional parameters
	 * @param $anchor string Name of desired anchor on the target page
	 */
	function redirect($journalPath = null, $page = null, $op = null, $path = null, $params = null, $anchor = null) {
		Request::redirectUrl(Request::url($journalPath, $page, $op, $path, $params, $anchor));
	}

	/**
	 * Get the journal path requested in the URL ("index" for top-level site requests).
	 * @return string
	 */
	function getRequestedJournalPath() {
		static $journal;

		if (!isset($journal)) {
			if (Request::isPathInfoEnabled()) {
				$journal = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 2) {
						$journal = Core::cleanFileVar($vars[1]);
					}
				}
			} else {
				$journal = Request::getUserVar('journal');
			}

			$journal = empty($journal) ? 'index' : $journal;
			HookRegistry::call('Request::getRequestedJournalPath', array(&$journal));
		}

		return $journal;
	}

	/**
	 * Get the journal associated with the current request.
	 * @return Journal
	 */
	function &getJournal() {
		static $journal;

		if (!isset($journal)) {
			$path = Request::getRequestedJournalPath();
			if ($path != 'index') {
				$journalDao =& DAORegistry::getDAO('JournalDAO');
				$journal = $journalDao->getJournalByPath(Request::getRequestedJournalPath());
			}
		}

		return $journal;
	}

	/**
	 * A Generic call to a context-defined path (e.g. a Journal or a Conference's path)
	 * @param $contextLevel int (optional) the number of levels of context to return in the path
	 * @return array of String (each element the path to one context element)
	 */
	function getRequestedContextPath($contextLevel = null) {
		//there is only one $contextLevel, so no need to check
		return array(Request::getRequestedJournalPath());
	}

	/**
	 * A Generic call to a context defining object (e.g. a Journal, a Conference, or a SchedConf)
	 * @return Journal
	 * @param $level int (optional) the desired context level
	 */
	function &getContext($level = 1) {
		$returner = false;
		switch ($level) {
			case 1:
				$returner =& Request::getJournal();
				break;
		}
		return $returner;
	}

	/**
	 * Get the object that represents the desired context (e.g. Conference or Journal)
	 * @param $contextName String specifying the page context
	 * @return Journal
	 */
	function &getContextByName($contextName) {
		$returner = false;
		switch ($contextName) {
			case 'journal':
				$returner =& Request::getJournal();
				break;
		}
		return $returner;
	}

	/**
	 * Build a URL into OJS.
	 * @param $journalPath string Optional path for journal to use
	 * @param $page string Optional name of page to invoke
	 * @param $op string Optional name of operation to invoke
	 * @param $path mixed Optional string or array of args to pass to handler
	 * @param $params array Optional set of name => value pairs to pass as user parameters
	 * @param $anchor string Optional name of anchor to add to URL
	 * @param $escape boolean Whether or not to escape ampersands for this URL; default false.
	 */
	function url($journalPath = null, $page = null, $op = null, $path = null,
			$params = null, $anchor = null, $escape = false) {
		return parent::url(array($journalPath), $page, $op, $path, $params, $anchor, $escape);
	}

	/**
	 * Redirect to user home page (or the role home page if the user has one role).
	 */
	function redirectHome() {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$user = Request::getUser();
		$userId = $user->getUserId();

		if ($journal =& Request::getJournal()) {
			// The user is in the journal context, see if they have one role only
			$roles =& $roleDao->getRolesByUserId($userId, $journal->getJournalId());
			if(count($roles) == 1) {
				$role = array_shift($roles);
				Request::redirect(null, $role->getRolePath());
			} else {
				Request::redirect(null, 'user');
			}
		} else {
			// The user is at the site context, check to see if they are
			// only registered in one place w/ one role
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journals =& $journalDao->getJournals();
			$roles = $roleDao->getRolesByUserId($userId);

			if(count($roles) == 1) {
				$role = array_shift($roles);
				$journal = $journalDao->getJournal($role->getJournalId());
				isset($journal) ? Request::redirect($journal->getPath(), $role->getRolePath()) :
								  Request::redirect('index', 'user');
			} else Request::redirect('index', 'user');
		}
	}
}

?>
