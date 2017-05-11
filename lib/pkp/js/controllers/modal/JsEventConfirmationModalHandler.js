/**
 * @file js/controllers/modal/JsEventConfirmationModalHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JsEventConfirmationModalHandler
 * @ingroup js_controllers_modal
 *
 * @brief A confirmation modal that generates a JS event.
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
	$.pkp.controllers.modal.JsEventConfirmationModalHandler =
			function($handledElement, options) {

		this.parent($handledElement, options);

		// Configure the event to be generated when
		// the modal closes.
		this.jsEvent_ = options.jsEvent;

		this.extraArguments_ = options.extraArguments;
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.modal.JsEventConfirmationModalHandler,
			$.pkp.controllers.modal.ConfirmationModalHandler);


	//
	// Private properties
	//
	/**
	 * An event to be generated when the confirmation button
	 * has been clicked.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.modal.JsEventConfirmationModalHandler.prototype.
			jsEvent_ = null;


	/**
	 * An array of extra information to be passed along with the event.
	 * @private
	 * @type {?Array}
	 */
	$.pkp.controllers.modal.JsEventConfirmationModalHandler.prototype.
			extraArguments_ = null;


	//
	// Protected methods
	//
	/** @inheritDoc */
	$.pkp.controllers.modal.JsEventConfirmationModalHandler.prototype.
			checkOptions = function(options) {

		// Check the mandatory options of the ModalHandler handler.
		if (!this.parent('checkOptions', options)) {
			return false;
		}

		// Check for our own mandatory options.
		// The cancel button and event are mandatory.
		return typeof options.cancelButton === 'string' &&
				typeof options.jsEvent === 'string';
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
	$.pkp.controllers.modal.JsEventConfirmationModalHandler.prototype.
			modalConfirm = function(dialogElement, event) {

		this.trigger(/** @type {string} */ (this.jsEvent_),
				/** @type {Array} */ (this.extraArguments_));
		this.modalClose(dialogElement);
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
