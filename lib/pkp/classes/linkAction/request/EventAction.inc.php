<?php
/**
 * @file classes/linkAction/request/EventAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EventAction
 * @ingroup linkAction_request
 *
 * @brief This action triggers a Javascript event.
 */


import('lib.pkp.classes.linkAction.request.LinkActionRequest');

class EventAction extends LinkActionRequest {
	/** @var string Target selector */
	var $targetSelector;

	/** @var string Event name */
	var $eventName;

	/** @var array Event options */
	var $options;

	/**
	 * Constructor
	 * @param $targetSelector string Selector for target to receive event.
	 * @param $eventName string Name of Javascript event to trigger.
	 */
	function __construct($targetSelector, $eventName, $options = array()) {
		parent::__construct();
		$this->targetSelector = $targetSelector;
		$this->eventName = $eventName;
		$this->options = $options;
	}


	//
	// Overridden protected methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getJSLinkActionRequest()
	 */
	function getJSLinkActionRequest() {
		return '$.pkp.classes.linkAction.EventAction';
	}

	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array_merge(
			$this->options,
			array(
				'target' => $this->targetSelector,
				'event' => $this->eventName,
			)
		);
	}
}

?>
