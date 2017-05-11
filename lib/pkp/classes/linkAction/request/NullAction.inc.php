<?php
/**
 * @file classes/linkAction/request/NullAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NullAction
 * @ingroup linkAction_request
 *
 * @brief This action does nothing.
 */


import('lib.pkp.classes.linkAction.request.LinkActionRequest');

class NullAction extends LinkActionRequest {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Overridden protected methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getJSLinkActionRequest()
	 */
	function getJSLinkActionRequest() {
		return '$.pkp.classes.linkAction.NullAction';
	}
}

?>
