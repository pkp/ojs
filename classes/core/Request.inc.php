<?php

/**
 * @file classes/core/Request.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup core
 *
 * @brief @verbatim Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<journal_id>/<page_name>/<operation_name>/<arguments...>
 * <journal_id> is assumed to be "index" for top-level site requests. @endverbatim
 */


import('lib.pkp.classes.core.PKPRequest');

class Request extends PKPRequest {
	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedContextPath()
	 */
	function getRequestedJournalPath() {
		static $journal;

		if (!isset($journal)) {
			$journal = $this->_delegateToRouter('getRequestedContextPath', 1);
			HookRegistry::call('Request::getRequestedJournalPath', array(&$journal));
		}

		return $journal;
	}

	/**
	 * @see PKPPageRouter::getContext()
	 */
	function &getJournal() {
		$returner = $this->_delegateToRouter('getContext', 1);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedContextPath()
	 */
	function getRequestedContextPath($contextLevel = null) {
		// Emulate the old behavior of getRequestedContextPath for
		// backwards compatibility.
		if (is_null($contextLevel)) {
			return $this->_delegateToRouter('getRequestedContextPaths');
		} else {
			return array($this->_delegateToRouter('getRequestedContextPath', $contextLevel));
		}
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContext()
	 */
	function &getContext($level = 1) {
		$returner = $this->_delegateToRouter('getContext', $level);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContextByName()
	 */
	function &getContextByName($contextName) {
		$returner = $this->_delegateToRouter('getContextByName', $contextName);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::url()
	 */
	function url($journalPath = null, $page = null, $op = null, $path = null,
			$params = null, $anchor = null, $escape = false) {
		return $this->_delegateToRouter('url', $journalPath, $page, $op, $path,
			$params, $anchor, $escape);
	}

	/**
	 * Deprecated
	 * @see PageRouter::redirectHome()
	 */
	function redirectHome() {
		return $this->_delegateToRouter('redirectHome');
	}
}


