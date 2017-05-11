/**
 * @file js/controllers/modal/AjaxModalHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AjaxModalHandler
 * @ingroup js_controllers_modal
 *
 * @brief A modal that retrieves content from a remote AJAX endpoint.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.modal.ModalHandler
	 *
	 * @param {jQueryObject} $handledElement The clickable element
	 *  the modal will be attached to.
	 * @param {Object} options non-default Dialog options
	 *  to be passed into the dialog widget.
	 *
	 *  Options are:
	 *  - url string the remote AJAX endpoint that will be used
	 *    to retrieve the content of the modal.
	 *  - all options documented for the jQueryUI dialog widget,
	 *    except for the buttons parameter which is not supported.
	 */
	$.pkp.controllers.modal.AjaxModalHandler = function($handledElement, options) {
		this.parent($handledElement, options);

		// We assume that AJAX modals usually contain forms and
		// therefore bind to form events by default.
		this.bind('formSubmitted', this.formSubmitted);
		this.bind('formCanceled', this.modalClose);
		this.bind('ajaxHtmlError', this.modalClose);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.modal.AjaxModalHandler,
			$.pkp.controllers.modal.ModalHandler);


	//
	// Protected methods
	//
	/** @inheritDoc */
	$.pkp.controllers.modal.AjaxModalHandler.prototype.checkOptions =
			function(options) {
		// Check the mandatory options of the ModalHandler handler.
		if (!this.parent('checkOptions', options)) {
			return false;
		}

		// Check for our own mandatory options.
		return typeof options.url === 'string';
	};


	/** @inheritDoc */
	$.pkp.controllers.modal.AjaxModalHandler.prototype.mergeOptions =
			function(options) {

		// Call parent.
		return /** @type {Object} */ (this.parent('mergeOptions', options));
	};


	/**
	 * Open the modal and fetch content via ajax
	 * @param {jQueryObject} $handledElement The clickable element
	 *  the modal will be attached to.
	 * @protected
	 */
	$.pkp.controllers.modal.AjaxModalHandler.prototype.modalOpen =
			function($handledElement) {
		this.parent('modalOpen', $handledElement);

		// Retrieve remote modal content.
		$handledElement.find('.content')
				.pkpAjaxHtml(/** @type {{ url: string }} */ (this.options).url);
	};


	/**
	 * Close the modal when a form submission is complete
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event} event The triggering event (e.g. a click on
	 *  a button.
	 * @protected
	 */
	$.pkp.controllers.modal.AjaxModalHandler.prototype.formSubmitted =
			function(callingContext, event) {

		this.getHtmlElement().parent().trigger('notifyUser');
		this.modalClose();
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
