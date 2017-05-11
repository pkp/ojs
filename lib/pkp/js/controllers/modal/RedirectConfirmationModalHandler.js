/**
 * @file js/controllers/modal/RedirectConfirmationModalHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RedirectConfirmationModalHandler
 * @ingroup js_controllers_modal
 *
 * @brief A confirmation modal that redirects to a URL upon confirmation.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.modal.ConfirmationModalHandler
	 *
	 * @param {jQueryObject} $handledElement The clickable element
	 *  the modal will be attached to.
	 * @param {Object} options Non-default options to configure
	 *  the modal.
	 *
	 *  Options are:
	 *  - remoteUrl string A URL to be redirected to when the confirmation
	 *    button has been clicked.
	 *  - All options from the ConfirmationModalHandler and ModalHandler
	 *    widgets.
	 *  - All options documented for the jQueryUI dialog widget,
	 *    except for the buttons parameter which is not supported.
	 */
	$.pkp.controllers.modal.RedirectConfirmationModalHandler =
			function($handledElement, options) {

		this.parent($handledElement, options);

		// Configure the redirect URL to be called when
		// the modal closes.
		this.remoteUrl_ = options.remoteUrl;
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.modal.RedirectConfirmationModalHandler,
			$.pkp.controllers.modal.ConfirmationModalHandler);


	//
	// Private properties
	//
	/**
	 * A URL to be redirected to when the confirmation button
	 * has been clicked.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.modal.RedirectConfirmationModalHandler.prototype.
			remoteUrl_ = null;


	//
	// Protected methods
	//
	/** @inheritDoc */
	$.pkp.controllers.modal.RedirectConfirmationModalHandler.prototype.
			checkOptions = function(options) {

		// Check the mandatory options of the ModalHandler handler.
		if (!this.parent('checkOptions', options)) {
			return false;
		}

		// Check for our own mandatory options.
		// The cancel button is mandatory for redirect confirmation modals.
		return typeof options.cancelButton === 'string' &&
				typeof options.remoteUrl === 'string';
	};


	//
	// Public methods
	//
	/**
	 * Callback that will be activated when the modal's
	 * confirm button is clicked.
	 *
	 * @param {HTMLElement} dialogElement The element the
	 *  dialog was created on.
	 * @param {Event} event The click event.
	 */
	$.pkp.controllers.modal.RedirectConfirmationModalHandler.prototype.
			modalConfirm = function(dialogElement, event) {

		document.location = this.remoteUrl_;
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
