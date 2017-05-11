<?php
/**
 * @file classes/linkAction/request/PostAndRedirectAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PostAndRedirectAction
 * @ingroup linkAction_request
 *
 * @brief Class defining a post and redirect action. See PostAndRedirectRequest.js
 * to detailed description.
 */


import('lib.pkp.classes.linkAction.request.RedirectAction');

class PostAndRedirectAction extends RedirectAction {

	/** @var string The url to be used for posting data */
	var $_postUrl;

	/**
	 * Constructor
	 * @param $postUrl string The target URL to post data.
	 * @param $redirectUrl string The target URL to redirect.
	 */
	function __construct($postUrl, $redirectUrl) {
		parent::__construct($redirectUrl);
		$this->_postUrl = $postUrl;
	}


	//
	// Getters and Setters
	//
	/**
  	 * Get the url to post data.
	 * @return string
	 */
	function getPostUrl() {
		return $this->_postUrl;
	}


	//
	// Overridden protected methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getJSLinkActionRequest()
	 */
	function getJSLinkActionRequest() {
		return '$.pkp.classes.linkAction.PostAndRedirectRequest';
	}

	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		$options = parent::getLocalizedOptions();
		return array_merge($options,
			array('postUrl' => $this->getPostUrl())
		);
	}
}

?>
