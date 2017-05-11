<?php
/**
 * @file classes/linkAction/request/ConfirmationModal.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConfirmationModal
 * @ingroup linkAction_request
 *
 * @brief Class defining a simple confirmation modal either with remote action or not.
 */


import('lib.pkp.classes.linkAction.request.Modal');

class ConfirmationModal extends Modal {
	/**
	 * @var string A translation key defining the text for the confirmation
	 * button of the modal.
	 */
	var $_okButton;

	/**
	 * @var string a translation key defining the text for the cancel
	 * button of the modal.
	 */
	var $_cancelButton;

	/**
	 * @var string a translation key defining the text for the dialog
	 *  text.
	 */
	var $_dialogText;

	/**
	 * Constructor
	 * @param $dialogText string The localized text to appear
	 *  in the dialog modal.
	 * @param $title string (optional) The localized modal title.
	 * @param $titleIcon string (optional) The icon to be used
	 *  in the modal title bar.
	 * @param $okButton string (optional) The localized text to
	 *  appear on the confirmation button.
	 * @param $cancelButton string (optional) The localized text to
	 *  appear on the cancel button.
	 * @param $canClose boolean (optional) Whether the modal will
	 *  have a close button.
	 * @param $width int (optional) Override the default width of 'auto'
	 *  for confirmation modals.  Useful for modals that display
	 *  large blocks of text.
	 */
	function __construct($dialogText, $title = null, $titleIcon = 'modal_confirm', $okButton = null, $cancelButton = null, $canClose = true, $width = MODAL_WIDTH_AUTO) {

		$title = (is_null($title) ? __('common.confirm') : $title);
		parent::__construct($title, $titleIcon, $canClose, $width);

		$this->_okButton = (is_null($okButton) ? __('common.ok') : $okButton);
		$this->_cancelButton = (is_null($cancelButton) ? __('common.cancel') : $cancelButton);
		$this->_dialogText = $dialogText;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the translation key for the confirmation
	 * button text.
	 * @return string
	 */
	function getOkButton() {
		return $this->_okButton;
	}

	/**
	 * Get the translation key for the cancel
	 * button text.
	 * @return string
	 */
	function getCancelButton() {
		return $this->_cancelButton;
	}

	/**
	 * Get the translation key for the dialog
	 * text.
	 * @return string
	 */
	function getDialogText() {
		return $this->_dialogText;
	}


	//
	// Overridden methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array_merge(parent::getLocalizedOptions(), array(
				'modalHandler' => '$.pkp.controllers.modal.ConfirmationModalHandler',
				'okButton' => $this->getOkButton(),
				'cancelButton' => $this->getCancelButton(),
				'dialogText' => $this->getDialogText()));
	}
}

?>
