<?php
/**
 * @file classes/handler/HandlerValidatorJournal.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidatorJournal
 * @ingroup handler_validation
 *
 * @brief Class to validate if a Journal is present
 */

import('lib.pkp.classes.handler.validation.HandlerValidator');

class HandlerValidatorJournal extends HandlerValidator {
	/**
	 * Constructor.
	 * @param $handler Handler the associated form
	 * @param $redirectToLogin bool Send to login screen on validation fail if true
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $additionalArgs Array URL arguments to include in request
	 */
	function HandlerValidatorJournal(&$handler, $redirectToLogin = false, $message = null, $additionalArgs = array()) {
		parent::HandlerValidator($handler, $redirectToLogin, $message, $additionalArgs);
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		$journal =& Request::getJournal();		
		return ($journal)?true:false;
	}
}

?>
