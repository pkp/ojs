<?php

/**
 * @file classes/core/Handler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
}

?>
