<?php

/**
 * @file classes/core/JSONMessage.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JSONMessage
 * @ingroup core
 *
 * @brief Class to represent a JSON (Javascript Object Notation) message.
 *
 */


class JSONMessage {
	/** @var string The status of an event (e.g. false if form validation fails). */
	var $_status;

	/** @var Mixed The message to be delivered back to the calling script. */
	var $_content;

	/** @var string ID for DOM element that will be replaced. */
	var $_elementId;

	/** @var array A JS event generated on the server side. */
	var $_event;

	/** @var array Set of additional attributes for special cases. */
	var $_additionalAttributes;

	/**
	 * Constructor.
	 * @param $status boolean The status of an event (e.g. false if form validation fails).
	 * @param $content Mixed The message to be delivered back to the calling script.
	 * @param $elementId string The DOM element to be replaced.
	 * @param $additionalAttributes array Additional data to be returned.
	 */
	function __construct($status = true, $content = '', $elementId = '0', $additionalAttributes = null) {
		// Set internal state.
		$this->setStatus($status);
		$this->setContent($content);
		$this->setElementId($elementId);
		if (isset($additionalAttributes)) {
			$this->setAdditionalAttributes($additionalAttributes);
		}
	}

	/**
	 * Get the status string
	 * @return string
	 */
	function getStatus () {
		return $this->_status;
	}

	/**
	 * Set the status string
	 * @param $status string
	 */
	function setStatus($status) {
		assert(is_bool($status));
		$this->_status = $status;
	}

	/**
	 * Get the content string
	 * @return mixed
	 */
	function getContent() {
		return $this->_content;
	}

	/**
	 * Set the content data
	 * @param $content mixed
	 */
	function setContent($content) {
		$this->_content = $content;
	}

	/**
	 * Get the elementId string
	 * @return string
	 */
	function getElementId() {
		return $this->_elementId;
	}

	/**
	 * Set the elementId string
	 * @param $elementId string
	 */
	function setElementId($elementId) {
		assert(is_string($elementId) || is_numeric($elementId));
		$this->_elementId = $elementId;
	}

	/**
	 * Set the event to trigger with this JSON message
	 * @param $eventName string
	 * @param $eventData string
	 */
	function setEvent($eventName, $eventData = null) {
		assert(is_string($eventName));

		// Construct the even as an associative array.
		$event = array('name' => $eventName);
		if(!is_null($eventData)) $event['data'] = $eventData;

		$this->_event = $event;
	}

	/**
	 * Get the event to trigger with this JSON message
	 * @return array
	 */
	function getEvent() {
		return $this->_event;
	}

	/**
	 * Get the additionalAttributes array
	 * @return array
	 */
	function getAdditionalAttributes() {
		return $this->_additionalAttributes;
	}

	/**
	 * Set the additionalAttributes array
	 * @param $additionalAttributes array
	 */
	function setAdditionalAttributes($additionalAttributes) {
		assert(is_array($additionalAttributes));
		$this->_additionalAttributes = $additionalAttributes;
	}

	/**
	 * Construct a JSON string to use for AJAX communication
	 * @return string
	 */
	function getString() {
		// Construct an associative array that contains all information we require.
		$jsonObject = array(
			'status' => $this->getStatus(),
			'content' => $this->getContent(),
			'elementId' => $this->getElementId()
		);
		if(is_array($this->getAdditionalAttributes())) {
			foreach($this->getAdditionalAttributes() as $key => $value) {
				$jsonObject[$key] = $value;
			}
		}
		if(is_array($this->getEvent())) {
			$jsonObject['event'] = $this->getEvent();
		}

		// Encode the object.
		$json = json_encode($jsonObject);

		if ($json === false) {
			error_log(json_last_error_msg());
		}

		return $json;
	}
}

?>
