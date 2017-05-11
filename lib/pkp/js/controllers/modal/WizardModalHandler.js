/**
 * @file js/controllers/modal/WizardModalHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WizardModalHandler
 * @ingroup js_controllers_modal
 *
 * @brief A modal that contains a wizard and handles its events.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.modal.AjaxModalHandler
	 *
	 * @param {jQueryObject} $handledElement The clickable element
	 *  the modal will be attached to.
	 * @param {Object} options non-default Dialog options
	 *  to be passed into the dialog widget.
	 *
	 *  Options are:
	 *  - all options documented for the AjaxModalHandler.
	 */
	$.pkp.controllers.modal.WizardModalHandler =
			function($handledElement, options) {

		this.parent($handledElement, options);

		// Subscribe the modal to wizard events.
		this.bind('wizardClose', this.wizardClose);
		this.bind('wizardCancel', this.wizardClose);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.modal.WizardModalHandler,
			$.pkp.controllers.modal.AjaxModalHandler);


	/**
	 * Overridden version of the modal close button handler acting
	 * as a wizard cancel button.
	 *
	 * @protected
	 * @param {Object=} opt_callingElement The close button.
	 * @param {Event=} opt_event The close button click event.
	 * @param {boolean=} opt_closeWithoutCancel Set to true to immediately
	 *  close the modal.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.modal.WizardModalHandler.prototype.modalClose =
			function(opt_callingElement, opt_event, opt_closeWithoutCancel) {

		if (opt_closeWithoutCancel) {
			this.parent('modalClose', opt_callingElement, opt_event);
		} else {
			// Trigger a cancel event on the wizard.
			var wizardCancelRequestedEvent = new $.Event('wizardCancelRequested'),
					$wizard;

			wizardCancelRequestedEvent.stopPropagation();
			$wizard = this.getHtmlElement().children().first();
			$wizard.trigger(wizardCancelRequestedEvent);

			// Only close the modal if the wizard didn't prevent this.
			if (!wizardCancelRequestedEvent.isDefaultPrevented()) {
				this.parent('modalClose', opt_callingElement, opt_event);
			}
		}

		return false;
	};


	/**
	 * Handle the wizard close event.
	 *
	 * @param {HTMLElement} wizardElement The calling
	 *  wizard.
	 * @param {Event} event The triggered event.
	 */
	$.pkp.controllers.modal.WizardModalHandler.prototype.wizardClose =
			function(wizardElement, event) {

		this.modalClose(wizardElement, event, true);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
