<?php
/**
 * @file classes/linkAction/request/OpenWindowAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OpenWindowAction
 * @ingroup linkAction_request
 *
 * @brief This action request redirects to another page.
 */


import('lib.pkp.classes.linkAction.request.LinkActionRequest');

class OpenWindowAction extends LinkActionRequest {
	/** @var string The URL this action will invoke */
	var $_url;

	/**
	 * Constructor
	 * @param $url string Target URL
	 */
	function __construct($url) {
		parent::__construct();
		$this->_url = $url;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the target URL.
	 * @return string
	 */
	function getUrl() {
		return $this->_url;
	}


	//
	// Overridden protected methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getJSLinkActionRequest()
	 */
	function getJSLinkActionRequest() {
		return '$.pkp.classes.linkAction.OpenWindowRequest';
	}

	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array('url' => $this->getUrl());
	}
}

?>
