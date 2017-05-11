<?php
/**
 * @file classes/linkAction/request/AjaxAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AjaxAction
 * @ingroup linkAction_request
 *
 * @brief Class defining an AJAX action.
 */


define('AJAX_REQUEST_TYPE_GET', 'get');
define('AJAX_REQUEST_TYPE_POST', 'post');

import('lib.pkp.classes.linkAction.request.LinkActionRequest');

class AjaxAction extends LinkActionRequest {

	/** @var string */
	var $_remoteAction;

	/** @var string */
	var $_requestType;


	/**
	 * Constructor
	 * @param $remoteAction string The target URL.
	 * @param $requestType string One of the AJAX_REQUEST_TYPE_* constants.
	 */
	function __construct($remoteAction, $requestType = AJAX_REQUEST_TYPE_POST) {
		parent::__construct();
		$this->_remoteAction = $remoteAction;
		$this->_requestType = $requestType;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the target URL.
	 * @return string
	 */
	function getRemoteAction() {
		return $this->_remoteAction;
	}

	/**
	 * Get the modal object.
	 * @return Modal
	 */
	function getRequestType() {
		return $this->_requestType;
	}


	//
	// Overridden protected methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getJSLinkActionRequest()
	 */
	function getJSLinkActionRequest() {
		return '$.pkp.classes.linkAction.AjaxRequest';
	}

	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array(
			'url' => $this->getRemoteAction(),
			'requestType' => $this->getRequestType()
		);
	}
}

?>
