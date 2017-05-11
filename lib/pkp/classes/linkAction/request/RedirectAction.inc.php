<?php
/**
 * @file classes/linkAction/request/RedirectAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RedirectAction
 * @ingroup linkAction_request
 *
 * @brief This action request redirects to another page.
 */


import('lib.pkp.classes.linkAction.request.LinkActionRequest');

class RedirectAction extends LinkActionRequest {
	/** @var string The URL this action will invoke */
	var $_url;

	/** @var string The name of the window */
	var $_name;

	/** @var string The specifications of the window */
	var $_specs;

	/**
	 * Constructor
	 * @param $url string Target URL
	 * @param $name string Name of window to direct (defaults to current window)
	 * @param $specs string Optional set of window specs (see window.open JS reference)
	 */
	function __construct($url, $name = '_self', $specs = '') {
		parent::__construct();
		$this->_url = $url;
		$this->_name = $name;
		$this->_specs = $specs;
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

	/**
	 * Get the target name.
	 * See JS reference for the name parameter to "window.open".
	 * @return string
	 */
	function getName() {
		return $this->_name;
	}

	/**
	 * Get the target specifications.
	 * See JS reference for the specs parameter to "window.open".
	 * @return string
	 */
	function getSpecs() {
		return $this->_specs;
	}


	//
	// Overridden protected methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getJSLinkActionRequest()
	 */
	function getJSLinkActionRequest() {
		return '$.pkp.classes.linkAction.RedirectRequest';
	}

	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array(
			'url' => $this->getUrl(),
			'name' => $this->getName(),
			'specs' => $this->getSpecs()
		);
	}
}

?>
