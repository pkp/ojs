<?php
/**
 * @file classes/linkAction/request/JsEventConfirmationModal.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JsEventConfirmationModal
 * @ingroup linkAction_request
 *
 * @brief Class defining a simple confirmation modal which generates a JS event and ok/cancel buttons.
 */


import('lib.pkp.classes.linkAction.request.ConfirmationModal');

class JsEventConfirmationModal extends ConfirmationModal {
	/** @var string The name of the event to be generated when this modal is confirmed */
	var $_event;

	/** @var array extra arguments to be passed to the JS controller */
	var $_extraArguments;

	/**
	 * Constructor
	 * @param $dialogText string The localized text to appear
	 *  in the dialog modal.
	 * @param $event string the name of the JS event.
	 * @param $extraArguments array (optional) extra information to be passed as JSON data with the event.
	 * @param $title string (optional) The localized modal title.
	 * @param $remoteUrl string (optional) A URL to be
	 *  redirected to when the confirmation button is clicked.
	 * @param $titleIcon string (optional) The icon to be used
	 *  in the modal title bar.
	 * @param $okButton string (optional) The localized text to
	 *  appear on the confirmation button.
	 * @param $cancelButton string (optional) The localized text to
	 *  appear on the cancel button.
	 * @param $canClose boolean (optional) Whether the modal will
	 *  have a close button.
	 */
	function __construct($dialogText, $event = 'confirmationModalConfirmed', $extraArguments = null, $title = null, $titleIcon = null, $okButton = null, $cancelButton = null, $canClose = true) {
		parent::__construct($dialogText, $title, $titleIcon, $okButton, $cancelButton, $canClose);

		$this->_event = $event;
		$this->_extraArguments = $extraArguments;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the event.
	 * @return string
	 */
	function getEvent() {
		return $this->_event;
	}

	/**
	 * Get the extra arguments.
	 * @return string
	 */
	function getExtraArguments() {
		return $this->_extraArguments;
	}


	//
	// Overridden methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		$parentLocalizedOptions = parent::getLocalizedOptions();
		// override the modalHandler option.
		$parentLocalizedOptions['modalHandler'] = '$.pkp.controllers.modal.JsEventConfirmationModalHandler';
		$parentLocalizedOptions['jsEvent'] = $this->getEvent();
		if (is_array($this->getExtraArguments())) {
			$json = new JSONMessage();
			$json->setContent($this->getExtraArguments());
			$parentLocalizedOptions['extraArguments'] = $json->getString();
		}
		return $parentLocalizedOptions;
	}
}

?>
