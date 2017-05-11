<?php
/**
 * @file classes/linkAction/request/RedirectConfirmationModal.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RedirectConfirmationModal
 * @ingroup linkAction_request
 *
 * @brief Class defining a simple confirmation modal with a redirect url and ok/cancel buttons.
 */


import('lib.pkp.classes.linkAction.request.ConfirmationModal');

class RedirectConfirmationModal extends ConfirmationModal {
	/** @var string A URL to be redirected to when the confirmation button is clicked. */
	var $_remoteUrl;

	/**
	 * Constructor
	 * @param $dialogText string The localized text to appear
	 *  in the dialog modal.
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
	function __construct($dialogText, $title = null, $remoteUrl = null, $titleIcon = null, $okButton = null, $cancelButton = null, $canClose = true) {
		parent::__construct($dialogText, $title, $titleIcon, $okButton, $cancelButton, $canClose);

		$this->_remoteUrl = $remoteUrl;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the remote url.
	 * @return string
	 */
	function getRemoteUrl() {
		return $this->_remoteUrl;
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
		$parentLocalizedOptions['modalHandler'] = '$.pkp.controllers.modal.RedirectConfirmationModalHandler';
		$parentLocalizedOptions['remoteUrl'] = $this->getRemoteUrl();
		return $parentLocalizedOptions;
	}
}

?>
