/**
 * @defgroup js_controllers
 */
/**
 * @file js/controllers/SiteHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteHandler
 * @ingroup js_controllers
 *
 * @brief Handle the site widget.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $widgetWrapper An HTML element that this handle will
	 * be attached to.
	 * @param {{
	 *   toggleHelpUrl: string,
	 *   toggleHelpOffText: string,
	 *   toggleHelpOnText: string,
	 *   fetchNotificationUrl: string,
	 *   requestOptions: Object
	 *   }} options Handler options.
	 */
	$.pkp.controllers.SiteHandler = function($widgetWrapper, options) {
		this.parent($widgetWrapper, options);

		this.options_ = options;
		this.unsavedFormElements_ = [];

		$('.go').button();

		this.bind('redirectRequested', this.redirectToUrl);
		this.bind('notifyUser', this.fetchNotificationHandler_);
		this.bind('updateHeader', this.updateHeaderHandler_);
		this.bind('callWhenClickOutside', this.callWhenClickOutsideHandler_);
		this.bind('mousedown', this.mouseDownHandler_);

		// Bind the pageUnloadHandler_ method to the DOM so it is
		// called.
		$(window).bind('beforeunload', this.pageUnloadHandler_);

		// Avoid IE8 caching ajax results. If it does, widgets like
		// grids will not refresh correctly.
		$.ajaxSetup({cache: false});

		// Check if we have notifications to show.
		if (options.hasSystemNotifications) {
			this.trigger('notifyUser');
		}

		// bind event handlers for form status change events.
		this.bind('formChanged', this.callbackWrapper(
				this.registerUnsavedFormElement_));
		this.bind('unregisterChangedForm', this.callbackWrapper(
				this.unregisterUnsavedFormElement_));
		this.bind('unregisterAllForms', this.callbackWrapper(
				this.unregisterAllFormElements_));

		// React to a modal events
		this.bind('pkpModalOpen', this.callbackWrapper(this.openModal_));
		this.bind('pkpModalClose', this.callbackWrapper(this.closeModal_));

		this.bind('pkpObserveScrolling', this.callbackWrapper(
				this.registerScrollingObserver_));
		this.bind('pkpRemoveScrollingObserver', this.callbackWrapper(
				this.unregisterScrollingObserver_));

		this.outsideClickChecks_ = {};

		this.initializeTinyMCE();
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.SiteHandler, $.pkp.classes.Handler);


	//
	// Private properties
	//
	/**
	 * Help context.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.SiteHandler.prototype.helpContext_ = null;


	/**
	 * Site handler options.
	 * @private
	 * @type {Object}
	 */
	$.pkp.controllers.SiteHandler.prototype.options_ = null;


	/**
	 * Object with data to be used when checking if user
	 * clicked outside a site element. See callWhenClickOutsideHandler_()
	 * to check the expected check options.
	 * @private
	 * @type {Object}
	 */
	$.pkp.controllers.SiteHandler.prototype.outsideClickChecks_ = null;


	/**
	 * A state variable to store the form elements that have unsaved data.
	 * @private
	 * @type {Array}
	 */
	$.pkp.controllers.SiteHandler.prototype.unsavedFormElements_ = null;


	//
	// Public static methods.
	//
	/**
	 * Initialize the tinyMCE plugin
	 */
	$.pkp.controllers.SiteHandler.prototype.initializeTinyMCE =
			function() {

		if (typeof tinyMCE !== 'undefined') {
			tinyMCE.PluginManager.load('jbimages', $.pkp.app.baseUrl +
					'/plugins/generic/tinymce/plugins/justboil.me/plugin.js');
			tinyMCE.PluginManager.load('pkpTags', $.pkp.app.baseUrl +
					'/plugins/generic/tinymce/plugins/pkpTags/plugin.js');

			var tinymceParams, tinymceParamDefaults = {
				width: '100%',
				resize: 'both',
				entity_encoding: 'raw',
				plugins: 'paste,fullscreen,link,code,-jbimages,-pkpTags,noneditable',
				convert_urls: false,
				forced_root_block: 'p',
				paste_auto_cleanup_on_paste: true,
				apply_source_formatting: false,
				theme: 'modern',
				toolbar: 'copy paste | bold italic underline | link unlink ' +
						'code fullscreen | jbimages | pkpTags',
				richToolbar: 'copy paste | bold italic underline | bullist numlist | ' +
						'superscript subscript | link unlink code fullscreen | ' +
						'jbimages | pkpTags',
				statusbar: false,
				content_css: $.pkp.app.baseUrl +
						'/plugins/generic/tinymce/styles/content.css'
			};

			// Allow default params to be overridden
			if (typeof $.pkp.plugins.tinymceplugin !== 'undefined' &&
					typeof $.pkp.plugins.tinymceplugin.tinymceParams) {
				tinymceParams = $.extend({}, tinymceParamDefaults,
						$.pkp.plugins.tinymceplugin.tinymceParams);
			} else {
				tinymceParams = $.extend({}, tinymceParamDefaults);
			}

			// Don't allow the following settings to be overridden
			tinymceParams.init_instance_callback =
					$.pkp.controllers.SiteHandler.prototype.triggerTinyMCEInitialized;
			tinymceParams.setup =
					$.pkp.controllers.SiteHandler.prototype.triggerTinyMCESetup;

			tinyMCE.init(tinymceParams);
		}
	};


	/**
	 * Callback used by the tinyMCE plugin to trigger the tinyMCEInitialized
	 * event in the DOM.
	 * @param {Object} tinyMCEObject The tinyMCE object instance being
	 * initialized.
	 */
	$.pkp.controllers.SiteHandler.prototype.triggerTinyMCEInitialized =
			function(tinyMCEObject) {

		var $inputElement = $('#' + tinyMCEObject.id);
		$inputElement.trigger('tinyMCEInitialized', [tinyMCEObject]);
	};


	/**
	 * Callback used by the tinyMCE plugin upon setup.
	 * @param {Object} tinyMCEObject The tinyMCE object instance being
	 * set up.
	 */
	$.pkp.controllers.SiteHandler.prototype.triggerTinyMCESetup =
			function(tinyMCEObject) {
		var target = $('#' + tinyMCEObject.id), height;

		// For read-only controls, set up TinyMCE read-only mode.
		if (target.attr('readonly')) {
			tinyMCEObject.settings.readonly = true;
		}

		// Set height based on textarea rows
		height = target.attr('rows') || 10; // default: 10
		height *= 20; // 20 pixels per row
		tinyMCEObject.settings.height = height.toString() + 'px';

		// Add a fake HTML5 placeholder when the editor is intitialized
		tinyMCEObject.on('init', function(tinyMCEObject) {
			var $element = $('#' + tinyMCEObject.id),
					placeholderText,
					$placeholder,
					$placeholderParent;

			// Don't add anything if we don't have a placeholder
			placeholderText = $('#' + tinyMCEObject.id).attr('placeholder');
			if (placeholderText === '') {
				return;
			}

			// Create placeholder element
			$placeholder = /** @type {jQueryObject} */ ($('<span></span>')
					.html(/** @type {string} */ (placeholderText)));
			$placeholder.addClass('mcePlaceholder');
			$placeholder.attr('id', 'mcePlaceholder-' + tinyMCEObject.id);

			if (tinyMCEObject.target.getContent().length) {
				$placeholder.hide();
			}

			// Create placeholder wrapper
			$placeholderParent = $('<div></div>');
			$placeholderParent.addClass('mcePlaceholderParent');
			$element.wrap($placeholderParent);
			$element.parent().append($placeholder);
		});

		tinyMCEObject.on('activate', function(tinyMCEObject) {
			// Hide the placeholder when the editor is activated
			$('#mcePlaceholder-' + tinyMCEObject.id).hide();
		});

		tinyMCEObject.on('deactivate', function(tinyMCEObject) {
			// Show the placholder when the editor is deactivated
			if (!tinyMCEObject.target.getContent().length) {
				$('#mcePlaceholder-' + tinyMCEObject.id).show();
			}
			tinyMCEObject.target.dom.addClass(
					tinyMCEObject.target.dom.select('li'), 'show');
		});

		tinyMCEObject.on('BeforeSetContent', function(e) {
			var variablesParsed = $.pkp.classes.TinyMCEHelper.prototype.getVariableMap(
					'#' + tinyMCEObject.id);

			e.content = e.content.replace(
					/\{\$([a-zA-Z]+)\}(?![^<]*>)/g, function(match, contents, offset, s) {
						if (variablesParsed[contents] !== undefined) {
							return $.pkp.classes.TinyMCEHelper.prototype.getVariableElement(
									contents, variablesParsed[contents]).html();
						}
						return match;
					});
		});

		// When the field is being saved, replace any tag placeholders
		tinyMCEObject.on('SaveContent', function(e) {
			var $content = $('<div>' + e.content + '</div>');

			// Replace tag span elements with the raw tags
			$content.find('.pkpTag').replaceWith(function() {
				return '{$' + $(this).attr('data-symbolic') + '}';
			});
			e.content = $content.html();
		});

		// In fullscreen mode, also present the toolbar.
		tinyMCEObject.on('FullscreenStateChanged init', function(e) {
			var target = e.target, $container = $(target.editorContainer);
			if (target.plugins.fullscreen) {
				if (target.plugins.fullscreen.isFullscreen()) {
					$container.find('.mce-toolbar[role=\'menubar\']').show();
				} else {
					$container.find('.mce-toolbar[role=\'menubar\']').hide();
				}
			}
		});
	};


	/**
	 * Get the current window dimensions.
	 * @return {Object} The current window dimensions (height and width)
	 * in pixels.
	 */
	$.pkp.controllers.SiteHandler.prototype.getWindowDimensions =
			function() {
		var dimensions = {'height': $(window).height(),
			'width': $(window).width()};

		return dimensions;
	};


	//
	// Public methods
	//
	/**
	 * Callback that is triggered when the page should redirect.
	 *
	 * @param {HTMLElement} sourceElement The element that issued the
	 *  "redirectRequested" event.
	 * @param {Event} event The "redirect requested" event.
	 * @param {string} url The URL to redirect to.
	 */
	$.pkp.controllers.SiteHandler.prototype.redirectToUrl =
			function(sourceElement, event, url) {

		window.location = url;
	};


	/**
	 * Handler bound to 'formChanged' events propagated by forms
	 * that wish to have their form data tracked.
	 * @private
	 * @param {HTMLElement} siteHandlerElement The html element
	 * attached to this handler.
	 * @param {HTMLElement} sourceElement The element wishes to
	 * register.
	 * @param {Event} event The formChanged event.
	 */
	$.pkp.controllers.SiteHandler.prototype.registerUnsavedFormElement_ =
			function(siteHandlerElement, sourceElement, event) {
		var $formElement, formId, index;

		$formElement = $(event.target.lastElementChild);
		formId = $formElement.attr('id');
		index = $.inArray(formId, this.unsavedFormElements_);
		if (index == -1) {
			this.unsavedFormElements_.push(formId);
		}
	};


	/**
	 * Handler bound to 'unregisterChangedForm' events propagated by forms
	 * that wish to inform that they no longer wish to be tracked as 'unsaved'.
	 * @private
	 * @param {HTMLElement} siteHandlerElement The html element
	 * attached to this handler.
	 * @param {HTMLElement} sourceElement The element that wishes to
	 * unregister.
	 * @param {Event} event The unregisterChangedForm event.
	 */
	$.pkp.controllers.SiteHandler.prototype.unregisterUnsavedFormElement_ =
			function(siteHandlerElement, sourceElement, event) {
		var $formElement, formId, index;

		$formElement = $(event.target.lastElementChild);
		formId = $formElement.attr('id');
		index = $.inArray(formId, this.unsavedFormElements_);
		if (index !== -1) {
			delete this.unsavedFormElements_[index];
		}
	};


	/**
	 * Unregister all unsaved form elements.
	 * @private
	 */
	$.pkp.controllers.SiteHandler.prototype.unregisterAllFormElements_ =
			function() {
		this.unsavedFormElements_ = [];
	};


	//
	// Private methods.
	//
	/**
	 * Fetch the notification data.
	 * @private
	 * @param {HTMLElement} sourceElement The element that issued the
	 *  "fetchNotification" event.
	 * @param {Event} event The "fetch notification" event.
	 * @param {Object} jsonData The JSON content representing the
	 *  notification.
	 */
	$.pkp.controllers.SiteHandler.prototype.fetchNotificationHandler_ =
			function(sourceElement, event, jsonData) {

		if (jsonData !== undefined) {
			// This is an event that came from an inplace notification
			// widget that was not visible because of the scrolling.
			this.showNotification_(jsonData);
			return;
		}

		// Avoid race conditions with in place notifications.
		$.ajax({
			url: this.options_.fetchNotificationUrl,
			data: this.options_.requestOptions,
			success: this.callbackWrapper(this.showNotificationResponseHandler_),
			dataType: 'json',
			async: false
		});
	};


	/**
	 * Fetch the header (e.g. on header configuration change).
	 * @private
	 * @param {HTMLElement} sourceElement The element that issued the
	 *  update header event.
	 * @param {Event} event The "fetch header" event.
	 */
	$.pkp.controllers.SiteHandler.prototype.updateHeaderHandler_ =
			function(sourceElement, event) {
		var handler = $.pkp.classes.Handler.getHandler($('#navigationUserWrapper'));
		handler.reload();
	};


	/**
	 * Call when click outside event handler. Stores the event
	 * parameters as checks to be used later by mouse down handler so we
	 * can track if user clicked outside the passed element or not.
	 * @private
	 * @param {HTMLElement} sourceElement The element that issued the
	 *  callWhenClickOutside event.
	 * @param {Event} event The "call when click outside" event.
	 * @param {{
	 *   container: jQueryObject,
	 *   callback: Function,
	 *   skipWhenVisibleModals: boolean
	 *   }} eventParams The event parameters.
	 * - container: a jQuery element to be used to test if user click
	 * outside of it or not.
	 * - callback: a callback function in case test is true.
	 * - skipWhenVisibleModals: boolean flag to tell whether skip the
	 * callback when modals are visible or not.
	 */
	$.pkp.controllers.SiteHandler.prototype.callWhenClickOutsideHandler_ =
			function(sourceElement, event, eventParams) {
		// Check the required parameters.
		if (eventParams.container === undefined) {
			return;
		}

		if (eventParams.callback === undefined) {
			return;
		}

		var id = eventParams.container.attr('id');
		this.outsideClickChecks_[id] = eventParams;
	};


	/**
	 * Mouse down event handler attached to the site element.
	 * @private
	 * @param {HTMLElement} sourceElement The element that issued the
	 *  click event.
	 * @param {Event} event The "mousedown" event.
	 * @return {?boolean} Event handling status.
	 */
	$.pkp.controllers.SiteHandler.prototype.mouseDownHandler_ =
			function(sourceElement, event) {

		var $container, callback, id;
		if (!$.isEmptyObject(this.outsideClickChecks_)) {
			for (id in this.outsideClickChecks_) {
				this.processOutsideClickCheck_(
						this.outsideClickChecks_[id], event);
			}
		}

		return true;
	};


	/**
	 * Check if the passed event target is outside the element
	 * inside the passed check data. If true and no other check
	 * option avoids it, use the callback.
	 * @private
	 * @param {{
	 *   skipWhenVisibleModals: boolean,
	 *   container: Object,
	 *   callback: Function
	 *   }} checkOptions Object with data to be used to
	 * check the click.
	 * @param {Event} event The click event to be checked.
	 * @return {boolean} Whether the check was processed or not.
	 */
	$.pkp.controllers.SiteHandler.prototype.processOutsideClickCheck_ =
			function(checkOptions, event) {

		// Make sure we have a click event.
		if (event.type !== 'click' &&
				event.type !== 'mousedown' && event.type !== 'mouseup') {
			throw new Error('Can not check outside click with the passed event: ' +
					event.type + '.');
		}

		// Get the container element.
		var $container = checkOptions.container;

		// Doesn't make sense to check an outside click
		// with an invisible element, so skip test if
		// container is hidden.
		if ($container.is(':hidden')) {
			return false;
		}

		// Check for the visible modals option.
		if (checkOptions.skipWhenVisibleModals !==
				undefined) {
			if (checkOptions.skipWhenVisibleModals) {
				if (this.getHtmlElement().find('div.ui-dialog').length > 0) {
					// Found a modal, return.
					return false;
				}
			}
		}

		// Do the click origin checking.
		if ($container.has(event.target).length === 0) {

			// Once the check was processed, delete it.
			delete this.outsideClickChecks_[$container.attr('id')];

			checkOptions.callback();

			return true;
		}

		return false;
	};


	/**
	 * Internal callback called upon page unload. If it returns
	 * anything other than void, a message will be displayed to
	 * the user.
	 * @private
	 * @param {Object} object The window object.
	 * @param {Event} event The before unload event.
	 * @return {string|undefined} The warning message string, if needed.
	 */
	$.pkp.controllers.SiteHandler.prototype.pageUnloadHandler_ =
			function(object, event) {
		var handler, unsavedElementCount, element;

		// any registered and then unregistered forms will exist
		// as properties in the unsavedFormElements_ object. They
		// will just be undefined.  See if there are any that are
		// not.

		// we need to get the handler this way since this event is bound
		// to window, not to SiteHandler.
		handler = $.pkp.classes.Handler.getHandler($('body'));

		unsavedElementCount = 0;
		for (element in handler.unsavedFormElements_) {
			if (element) {
				unsavedElementCount++;
			}
		}
		if (unsavedElementCount > 0) {
			return $.pkp.locale.form_dataHasChanged;
		}
		return undefined;
	};


	/**
	 * Method to determine if a form is currently registered as having
	 * unsaved changes.
	 *
	 * @param {string} id the id of the form to check.
	 * @return {boolean} true if the form is unsaved.
	 */
	$.pkp.controllers.SiteHandler.prototype.isFormUnsaved =
			function(id) {

		if (this.unsavedFormElements_ !== null &&
				this.unsavedFormElements_[id] !== undefined) {
			return true;
		}
		return false;
	};


	/**
	 * Response handler to the notification fetch.
	 * @private
	 * @param {Object} ajaxContext The data returned from the server.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.SiteHandler.prototype.showNotificationResponseHandler_ =
			function(ajaxContext, jsonData) {
		this.showNotification_(jsonData);
	};


	/**
	 * Show the notification content.
	 * @private
	 * @param {Object} jsonData The JSON-encoded notification data.
	 */
	$.pkp.controllers.SiteHandler.prototype.showNotification_ =
			function(jsonData) {
		var workingJsonData, notificationsData, levelId, notificationId, pnotify;

		workingJsonData = this.handleJson(jsonData);
		if (workingJsonData !== false) {
			if (workingJsonData.content.general) {
				notificationsData = workingJsonData.content.general;
				for (levelId in notificationsData) {
					for (notificationId in notificationsData[levelId]) {
						pnotify = new PNotify(notificationsData[levelId][notificationId]);
					}
				}
			}
		}
	};


	/**
	 * Reacts to a modal being opened. Adds a class to the body representing
	 * a modal open state.
	 * @private
	 * @param {HTMLElement} handledElement The modal that has been added
	 * @param {HTMLElement} siteHandlerElement The html element
	 * attached to this handler.
	 * @param {HTMLElement} sourceElement The element wishes to
	 * register.
	 * @param {Event} event The formChanged event.
	 */
	$.pkp.controllers.SiteHandler.prototype.openModal_ =
			function(handledElement, siteHandlerElement, sourceElement, event) {
		this.getHtmlElement().addClass('modal_is_visible');
	};


	/**
	 * Reacts to a modal being closed. Removes a class from the body
	 * representing a modal closed state, after checking if no other modals are
	 * open.
	 * @private
	 * @param {HTMLElement} handledElement The modal that has been added
	 * @param {HTMLElement} siteHandlerElement The html element
	 * attached to this handler.
	 * @param {HTMLElement} sourceElement The element wishes to
	 * register.
	 * @param {Event} event The formChanged event.
	 */
	$.pkp.controllers.SiteHandler.prototype.closeModal_ =
			function(handledElement, siteHandlerElement, sourceElement, event) {

		var $htmlElement = this.getHtmlElement();
		if (!$htmlElement.find('.pkp_modal.is_visible').length) {
			$htmlElement.removeClass('modal_is_visible');
		}
	};


	/**
	 * Register a function to observe the body scrolling event.
	 * @private
	 * @param {Object} siteHandler The site handler object.
	 * @param {HTMLElement} siteHandlerElement The html element
	 * attached to this handler.
	 * @param {Object} event The pkpObserveScrolling event object.
	 * @param {Function} observerFunction The observer function.
	 * @return {boolean}
	 */
	$.pkp.controllers.SiteHandler.prototype.registerScrollingObserver_ =
			function(siteHandler, siteHandlerElement, event, observerFunction) {
		$(document).scroll(observerFunction);
		return false;
	};


	/**
	 * Unregister a function that was observing the body scrolling event.
	 * @private
	 * @param {Object} siteHandler The site handler object.
	 * @param {HTMLElement} siteHandlerElement The html element
	 * attached to this handler.
	 * @param {Object} event The pkpRemoveScrollingObserver event object.
	 * @param {Function} observerFunction The observer function.
	 * @return {boolean}
	 */
	$.pkp.controllers.SiteHandler.prototype.unregisterScrollingObserver_ =
			function(siteHandler, siteHandlerElement, event, observerFunction) {
		var castObserverFunction = /** @type {function()} */ observerFunction;
		$(document).unbind('scroll', castObserverFunction);
		return false;
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
