<?php

/**
 * @file classes/security/Validation.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Validation
 * @ingroup security
 *
 * @brief Class providing user validation/authentication operations.
 */

import('lib.pkp.classes.security.PKPValidation');
import('classes.security.Role');

class Validation extends PKPValidation {

	/**
	 * Shortcut for checking authorization as journal manager.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isJournalManager($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_MANAGER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as editor.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isEditor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_EDITOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as section editor.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isSectionEditor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_SECTION_EDITOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as reviewer.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isReviewer($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_REVIEWER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as author.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isAuthor($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_AUTHOR, $journalId);
	}

	/**
	 * Shortcut for checking authorization as reader.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isReader($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_READER, $journalId);
	}

	/**
	 * Shortcut for checking authorization as subscription manager.
	 * @param $journalId int
	 * @return boolean
	 */
	static function isSubscriptionManager($journalId = -1) {
		return Validation::isAuthorized(ROLE_ID_SUBSCRIPTION_MANAGER, $journalId);
	}
}

?>
