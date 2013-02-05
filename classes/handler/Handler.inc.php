<?php

/**
 * @file classes/core/Handler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Handler
 * @ingroup handler
 *
 * @brief Base request handler application class
 */


import('lib.pkp.classes.handler.PKPHandler');
import('classes.handler.validation.HandlerValidatorJournal');
import('classes.handler.validation.HandlerValidatorSubmissionComment');

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
		// Check for multiple presses.
		$journalDao = DAORegistry::getDAO('JournalDAO');

		$user = $request->getUser();
		if (is_a($user, 'User')) {
			return $journalDao->getAll();
		} else {
			return $journalDao->getAll(true); // Enabled only
		}
	}
}

?>
