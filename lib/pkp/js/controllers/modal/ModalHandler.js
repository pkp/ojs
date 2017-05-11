/**
 * @defgroup js_controllers_modal
 */
/**
 * @file js/controllers/modal/ModalHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ModalHandler
 * @ingroup js_controllers_modal
 *
 * @brief Basic modal implementation.
 *
 *  A modal that has only one button and expects a simple message string.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.modal = $.pkp.controllers.modal || { };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $handledElement The modal.
	 * @param {Object.<string, *>} options The modal options.
	 */
	$.pkp.controllers.modal.ModalHandler = function($handledElement, options) {
		this.parent($handledElement, options);

		// Check the options.
		if (!this.checkOptions(options)) {
			throw new Error('Missing or invalid modal options!');
		}

		// Clone the options object before we manipulate them.
		var internalOptions = $.extend(true, {}, options),
				canClose;

		// Merge user and default options.
		this.options = /** @type {{ canClose: boolean, title: string,
				titleIcon: string }} */ (this.mergeOptions(internalOptions));

		// Attach content to the modal
		$handledElement.html(this.modalBuild()[0].outerHTML);

		// Open the modal
		this.modalOpen($handledElement);

		// Set up close controls
		$handledElement.find(
				'.pkpModalCloseButton').click(this.callbackWrapper(this.modalClose));
		$handledElement.on(
				'click keyup', this.callbackWrapper(this.handleWrapperEvents));

		// Publish some otherwise private events triggered
		// by nested widgets so that they can be handled by
		// the element that opened the modal.
		this.publishEvent('redirectRequested');
		this.publishEvent('dataChanged');
		this.publishEvent('containerReloadRequested');
		this.publishEvent('updateHeader');
		this.publishEvent('gridRefreshRequested');

		// Bind notify user event.
		this.bind('notifyUser', this.redirectNotifyUserEventHandler_);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.modal.ModalHandler,
			$.pkp.classes.Handler);


	//
	// Private static properties
	//
	/**
	 * Default options
	 * @private
	 * @type {Object}
	 * @const
	 */
	$.pkp.controllers.modal.ModalHandler.DEFAULT_OPTIONS_ = {
		autoOpen: true,
		width: 710,
		modal: true,
		draggable: false,
		resizable: false,
		position: {my: 'center', at: 'center center-10%', of: window},
		canClose: true
	};


	//
	// Public properties
	//
	/**
	 * Current options
	 *
	 * After passed options are merged with defaults.
	 *
	 * @type {Object}
	 */
	$.pkp.controllers.modal.ModalHandler.options = null;


	//
	// Protected methods
	//
	/**
	 * Check whether the correct options have been
	 * given for this modal.
	 * @protected
	 * @param {Object.<string, *>} options Modal options.
	 * @return {boolean} True if options are ok.
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.checkOptions =
			function(options) {

		// Check for basic configuration requirements.
		return typeof options === 'object' &&
				(/** @type {{ buttons: Object }} */ options).buttons === undefined;
	};


	/**
	 * Determine the options based on
	 * default options.
	 * @protected
	 * @param {Object.<string, *>} options Non-default modal options.
	 * @return {Object.<string, *>} The default options merged
	 *  with the non-default options.
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.mergeOptions =
			function(options) {

		// Merge the user options into the default options.
		var mergedOptions = $.extend(true, { },
				this.self('DEFAULT_OPTIONS_'), options);
		return mergedOptions;
	};


	//
	// Public methods
	//
	/**
	 * Build the markup for a modal container, including the header, close
	 * button and a container for the content to be placed in.
	 * TODO: This kind of markup probably shouldn't be embedded within the JS...
	 *
	 * @protected
	 * @return {Object} jQuery object representing modal content
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.modalBuild =
			function() {

		var $modal = $('<div class="pkp_modal_panel"></div>');

		// Title bar
		if (this.options.title !== 'undefined') {
			$modal.append('<div class="header">' + this.options.title + '</div>');
		}

		// Close button
		if (this.options.canClose) {
			$modal.append(
					'<a href="#" class="close pkpModalCloseButton">' +
					'<span class="pkp_screen_reader">' +
					(/** @type {{ closeButtonText: string }} */ (this.options))
					.closeButtonText + '</span></a>');
		}

		// Content
		$modal.append('<div class="content"></div>');

		// Add aria role and label
		$modal.attr('role', 'dialog')
				.attr('aria-label', this.options.title);

		return $modal;
	};


	/**
	 * Attach a modal to the dom and make it visible
	 * @param {jQueryObject} $handledElement The modal.
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.modalOpen =
			function($handledElement) {

		// The $handledElement must be attached to the DOM before events will
		// bubble up to SiteHandler
		var $body = $('body');
		$body.append($handledElement);

		// Trigger visibility state change on the next tick, so that CSS
		// transform animations will run
		setTimeout(function() {
			$handledElement.addClass('is_visible');
		},10);

		// Set focus to the modal. Leave a sizeable delay here so that the
		// element can be added to the dom first
		setTimeout(function() {
			$handledElement.focus();
		}, 300);

		// Trigger events
		$handledElement.trigger('pkpModalOpen', [$handledElement]);
	};


	/**
	 * Close the modal. Typically invoked via an event of some kind, such as
	 * a `click` or `keyup`
	 *
	 * @param {Object=} opt_callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a close button. Not set if called via callback.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.modalClose =
			function(opt_callingContext, opt_event) {

		var modalHandler = this,
				$modalElement = this.getHtmlElement(),
				$form = $modalElement.find('form').first(),
				handler, informationObject;

		// Unregister a form if attached to this modalElement
		// modalClose is called on both 'cancel' and 'close' events.  With
		// callbacks both callingContext and event are undefined. So,
		// unregister this form with SiteHandler.
		if ($form.length == 1) {
			informationObject = {closePermitted: true};
			$form.trigger('containerClose', [informationObject]);
			if (!informationObject.closePermitted) {
				return false;
			}
		}

		// Hide the modal, remove it from the DOM and remove the handler once
		// the CSS animation is complete
		$modalElement.removeClass('is_visible');
		this.trigger('pkpModalClose');
		setTimeout(function() {
			modalHandler.getHtmlElement().empty();
			modalHandler.remove();
		}, 300);

		return false;
	};


	/**
	 * Process events that reach the wrapper element.
	 * Should NOT block other events from bubbling up. Doing so
	 * can disable submit buttons in nested forms.
	 *
	 * @param {Object=} opt_callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a close button. Not set if called via callback.
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.handleWrapperEvents =
			function(opt_callingContext, opt_event) {

		// Close click events directly on modal (background screen)
		if (opt_event.type == 'click' && opt_callingContext == opt_event.target) {
			$.pkp.classes.Handler.getHandler($(opt_callingContext))
					.modalClose();
			return;
		}

		// Close for ESC keypresses (27) that have bubbled up
		if (opt_event.type == 'keyup' && opt_event.which == 27) {
			$.pkp.classes.Handler.getHandler($(opt_callingContext))
					.modalClose();
			return;
		}
	};


	//
	// Private methods
	//
	/**
	 * Handler to redirect to the correct notification widget the
	 * notify user event.
	 * @param {HTMLElement} sourceElement The element that issued the
	 * "notifyUser" event.
	 * @param {Event} event The "notify user" event.
	 * @param {HTMLElement} triggerElement The element that triggered
	 * the "notifyUser" event.
	 * @private
	 */
	$.pkp.controllers.modal.ModalHandler.prototype.redirectNotifyUserEventHandler_ =
			function(sourceElement, event, triggerElement) {

		// Use the notification helper to redirect the notify user event.
		$.pkp.classes.notification.NotificationHelper.
				redirectNotifyUserEvent(this, triggerElement);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
