<?php
/**
 * @defgroup linkAction_request Link Action Request
 */

/**
 * @file classes/linkAction/request/LinkActionRequest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LinkActionRequest
 * @ingroup linkAction_request
 *
 * @brief Abstract base class defining an action to be taken when a link action is activated.
 */

class LinkActionRequest {
	/**
	 * Constructor
	 */
	function __construct() {
	}


	//
	// Public methods
	//
	/**
	 * Return the JavaScript controller that will
	 * handle this request.
	 * @return string
	 */
	function getJSLinkActionRequest() {
		assert(false);
	}

	/**
	 * Return the options to be passed on to the
	 * JS action request handler.
	 * @return array An array describing the dialog
	 *  options.
	 */
	function getLocalizedOptions() {
		return array();
	}
}

?>
