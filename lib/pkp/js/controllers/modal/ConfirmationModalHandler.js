/**
 * @file js/controllers/modal/ConfirmationModalHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConfirmationModalHandler
 * @ingroup js_controllers_modal
 *
 * @brief A modal that displays a static explanatory text and has cancel and
 *  confirmation buttons.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.modal.ModalHandler
	 *
	 * @param {jQueryObject} $handledElement The clickable element
	 *  the modal will be attached to.
	 * @param {Object.<string, *>} options Non-default options to configure
	 *  the modal.
	 *
	 *  Options are:
	 *  - okButton string the name for the confirmation button.
	 *  - cancelButton string the name for the cancel button
	 *    (or false for no button).
	 *  - dialogText string the text to be displayed in the modal.
	 *  - All options from the ModalHandler widget.
	 */
	$.pkp.controllers.modal.ConfirmationModalHandler =
			function($handledElement, options) {

		this.parent($handledElement, options);

		// Bind to the confirmation button
		$handledElement.find('.pkpModalConfirmButton')
				.on('click', this.callbackWrapper(this.modalConfirm));
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.modal.ConfirmationModalHandler,
			$.pkp.controllers.modal.ModalHandler);


	//
	// Protected methods
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.modal.ConfirmationModalHandler.prototype.checkOptions =
			function(options) {
		// Check the mandatory options of the ModalHandler handler.
		if (!this.parent('checkOptions', options)) {
			return false;
		}

		// Hack to prevent closure compiler type mismatches
		var castOptions = /** @type {{okButton: string,
				cancelButton: string, dialogText: string}} */ options;

		// Check for our own mandatory options.
		return typeof castOptions.okButton === 'string' &&
				(/** @type {boolean} */ (castOptions.cancelButton) === false ||
				typeof castOptions.cancelButton === 'string') &&
				typeof castOptions.dialogText === 'string';
	};


	//
	// Public methods
	//
	/**
	 * Add content to modal
	 *
	 * @return {jQueryObject} jQuery object representing modal content
	 */
	$.pkp.controllers.modal.ConfirmationModalHandler.prototype.modalBuild =
			function() {

		var $modal = this.parent('modalBuild'),
				buttons = '<a href="#" class="ok pkpModalConfirmButton">' +
				(/** @type {{ okButton: string }} */ (this.options)).okButton +
				'</a>';

		$modal.addClass('pkp_modal_confirmation').find('.content')
				.append('<div class="message">' +
				(/** @type {{ dialogText: string }} */ (this.options)).dialogText +
				'</div>');

		if (this.options.cancelButton) {
			buttons += '<a href="#" class="cancel pkpModalCloseButton">' +
					this.options.cancelButton + '</a>';
		}

		$modal.append('<div class="footer">' + buttons + '</div>');

		// Add aria role and label
		$modal.attr('role', 'dialog')
				.attr('aria-label', this.options.title);

		return /** @type {jQueryObject} */ $modal;
	};


	/**
	 * Callback that will be activated when the modal's
	 * confirm button is clicked.
	 *
	 * @param {HTMLElement} dialogElement The element the
	 *  dialog was created on.
	 * @param {Event} event The click event.
	 */
	$.pkp.controllers.modal.ConfirmationModalHandler.prototype.modalConfirm =
			function(dialogElement, event) {

		// The default implementation will simply close the modal.
		this.modalClose(dialogElement);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
