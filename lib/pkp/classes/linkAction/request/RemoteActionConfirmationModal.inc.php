<?php
/**
 * @file classes/linkAction/request/RemoteActionConfirmationModal.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RemoteActionConfirmationModal
 * @ingroup linkAction_request
 *
 * @brief Class defining a simple confirmation modal with a remote action and ok/cancel buttons.
 */


import('lib.pkp.classes.linkAction.request.ConfirmationModal');

class RemoteActionConfirmationModal extends ConfirmationModal {
	/** @var string A URL to be called when the confirmation button is clicked. */
	var $_remoteAction;

	/** @var string A CSRF token. */
	var $_csrfToken;

	/**
	 * Constructor
	 * @param $session Session The user's session object.
	 * @param $dialogText string The localized text to appear
	 *  in the dialog modal.
	 * @param $title string (optional) The localized modal title.
	 * @param $remoteAction string (optional) A URL to be
	 *  called when the confirmation button is clicked.
	 * @param $titleIcon string (optional) The icon to be used
	 *  in the modal title bar.
	 * @param $okButton string (optional) The localized text to
	 *  appear on the confirmation button.
	 * @param $cancelButton string (optional) The localized text to
	 *  appear on the cancel button.
	 * @param $canClose boolean (optional) Whether the modal will
	 *  have a close button.
	 */
	function __construct($session, $dialogText, $title = null, $remoteAction = null, $titleIcon = null, $okButton = null, $cancelButton = null, $canClose = true) {
		parent::__construct($dialogText, $title, $titleIcon, $okButton, $cancelButton, $canClose);

		$this->_remoteAction = $remoteAction;
		$this->_csrfToken = $session->getCSRFToken();
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the remote action.
	 * @return string
	 */
	function getRemoteAction() {
		return $this->_remoteAction;
	}

	/**
	 * Get the CSRF token.
	 * @return string
	 */
	function getCSRFToken() {
		return $this->_csrfToken;
	}


	//
	// Overridden methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array_merge(
			parent::getLocalizedOptions(),
			array(
				'modalHandler' => '$.pkp.controllers.modal.RemoteActionConfirmationModalHandler',
				'remoteAction' => $this->getRemoteAction(),
				'csrfToken' => $this->getCSRFToken(),
			)
		);
	}
}

?>
