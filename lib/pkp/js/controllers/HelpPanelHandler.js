/**
 * @file js/controllers/HelpPanelHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HelpPanelHandler
 * @ingroup js_controllers
 *
 * @brief A handler for the fly-out contextual help panel.
 *
 * Listens: pkp.HelpPanel.Open
 * Listens: pkp.HelpPanel.Close
 * Emits: pkp.HelpPanel.Open
 * Emits: pkp.HelpPanel.Close
 *
 * This handler expects to be be attached to an element which matches the
 * following base markup. There should only be one help panel on any page.
 *
 * <div id="pkpHelpPanel" tabindex="-1">
 *   <div>
 *     <!-- This handler should only ever interact with the .content div. -->
 *     <div class="content"></div>
 *     <button class="pkpCloseHelpPanel"></button>
 *   </div>
 * </div>
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $element The outer <div> element
	 * @param {Object} options Handler options.
	 */
	$.pkp.controllers.HelpPanelHandler = function($element, options) {

		this.parent($element, {});

		// Let help link events bubble up to the body tag. This way we only
		// register one listener once per page load. We won't need to worry
		// about content getting swapped out on the page either, as any new
		// help links loaded will bubble up to the body tag.
		$('body').click(function(e) {
			if (!$(e.target).hasClass('requestHelpPanel')) {
				return;
			}
			e.preventDefault();
			var $self = $(e.target),
					options = $.extend({}, $self.data(), {caller: $self});
			$element.trigger('pkp.HelpPanel.Open', options);
		});

		// Register click handler on close button
		$element.find('.pkpCloseHelpPanel').click(function(e) {
			e.preventDefault();
			$element.trigger('pkp.HelpPanel.Close');
		});

		// Register click handler on home button
		$element.find('.pkpHomeHelpPanel').click(function(e) {
			e.preventDefault();
			$element.trigger('pkp.HelpPanel.Home');
		});

		// Handlers for "next" and "previous" buttons
		$element.find('.pkpPreviousHelpPanel')
				.click(this.callbackWrapper(function(e) {
					this.loadHelpContent_(this.previousTopic_, this.helpLocale_);
				}));
		$element.find('.pkpNextHelpPanel').click(this.callbackWrapper(function(e) {
			this.loadHelpContent_(this.nextTopic_, this.helpLocale_);
		}));

		// Register listeners
		$element.on('pkp.HelpPanel.Open', this.callbackWrapper(this.openPanel_))
				.on('pkp.HelpPanel.Close', this.callbackWrapper(this.closePanel_))
				.on('pkp.HelpPanel.Home', this.callbackWrapper(this.homePanel_));

		this.helpUrl_ = options.helpUrl;
		this.helpLocale_ = options.helpLocale;
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.HelpPanelHandler, $.pkp.classes.Handler);


	//
	// Private properties
	//
	/**
	 * Calling element. Focus will be returned here when help panel is closed
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.caller_ = null;


	/**
	 * Help subsystem's URL. Used to fetch help content for presentation.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.helpUrl_ = null;


	/**
	 * Help subsystem's locale. Default: `en`
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.helpLocale_ = null;


	/**
	 * Current help topic
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.currentTopic_ = null;


	/**
	 * Previous help topic
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.previousTopic_ = null;


	/**
	 * Next help topic
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.nextTopic_ = null;


	//
	// Private methods
	//
	/**
	 * Open the helper panel
	 * @private
	 * @param {HTMLElement} context The context in which this function was called
	 * @param {Event} event The event triggered on this handler
	 * @param {{
	 *  caller: jQueryObject,
	 *  topic: string
	 *  }} options The options with which to open this handler
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.openPanel_ =
			function(context, event, options) {

		var $element = this.getHtmlElement();

		// Save the calling element
		if (typeof options.caller !== 'undefined') {
			this.caller_ = options.caller;
		}

		// Show the help panel
		$element.addClass('is_visible');
		$('body').addClass('help_panel_is_visible'); // manage scrollbars

		// Listen to close interaction events
		$element.on('click.pkp.HelpPanel keyup.pkp.HelpPanel',
				this.callbackWrapper(this.handleWrapperEvents));

		// Listen to clicks on links
		$element.on('click.pkp.HelpPanelContentLink', '.content a',
				this.callbackWrapper(this.handleContentLinks_));

		// Load the appropriate help content
		this.loadHelpContent_(options.topic, this.helpLocale_);

		// Set focus inside the help panel (delay is required so that element is
		// visible when jQuery tries to focus on it)
		// @todo This should only happen once content is loaded in
		setTimeout(function() {
			$element.focus();
		}, 300);

	};


	/**
	 * Load help content in the panel.
	 * @param {string?} topic The help context.
	 * @param {string?} locale The language locale to load the help topic.
	 * @private
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.loadHelpContent_ =
			function(topic, locale) {
		locale = locale || this.helpLocale_;
		this.currentTopic_ = topic || '';
		var url = this.helpUrl_ + '/index/' + locale + '/';

		this.getHtmlElement().addClass('is_loading');

		// Don't escape slashes
		url += encodeURIComponent(this.currentTopic_).replace(/%2F/g, '/');

		$.get(url, null, this.callbackWrapper(this.updateContentHandler_),
				'json');
	};


	/**
	 * A callback to update the tabs on the interface.
	 * @private
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.
			updateContentHandler_ = function(ajaxContext, jsonData) {
		var workingJsonData = this.handleJson(jsonData),
				responseObject = workingJsonData.content,
				helpPanelHandler = this,
				$element = this.getHtmlElement(),
				hashIndex = this.currentTopic_.indexOf('#'),
				$targetHash,
				panel = $element.find('.panel');

		this.previousTopic_ = responseObject.previous;
		this.nextTopic_ = responseObject.next;

		// Place the new content into the DOM
		$element.find('.content').replaceWith(
				'<div class="content">' + responseObject.content + '</div>');

		// If a hash was specified, scroll to the named anchor.
		panel.scrollTop(0);
		if (hashIndex !== -1) {
			$targetHash = $element.find(
					'a[name=' + this.currentTopic_.substr(hashIndex + 1) + ']');
			if ($targetHash.length) {
				panel.scrollTop($targetHash.offset().top - 50);
			}
		}

		this.getHtmlElement().removeClass('is_loading');
	};


	/**
	 * A callback to handle clicks on links in the help content
	 *
	 * This function will allow external links to open in a new window but take
	 * control of relative links and try to open the appropriate help topic.
	 *
	 * @private
	 * @param {HTMLElement} target The target element the event was triggered on
	 * @param {Event} event The event triggered on this handler
	 * @return {boolean} Event handling status.
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.
			handleContentLinks_ = function(target, event) {

		var url = $(target).attr('href'),
				urlParts;

		event.preventDefault();

		// External links aren't yet supported in the help docs
		// See: https://github.com/pkp/pkp-lib/issues/1032#issuecomment-199342940
		if (url.substring(0, 4) == 'http') {
			window.open(url);
		} else {
			urlParts = url.split('/');
			this.loadHelpContent_(urlParts.slice(1).join('/'), urlParts[0]);
		}

		return false;
	};


	/**
	 * Close the helper panel
	 * @private
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.closePanel_ = function() {

		// Get a reference to this handler's element as a jQuery object
		var $element = this.getHtmlElement();

		// Show the help panel
		$element.removeClass('is_visible');
		$('body').removeClass('help_panel_is_visible'); // manage scrollbars

		// Clear the help content
		$element.find('.content').empty();

		// Set focus back to the calling element
		if (this.caller_ !== null) {
			this.caller_.focus();
		}

		// Unbind wrapper events from element and reset vars
		$element.off('click.pkp.HelpPanel keyup.pkp.HelpPanel');
		$element.off('click.pkp.HelpPanelContentLink', '.content a');
		this.caller_ = null;
	};


	/**
	 * Home the helper panel
	 * @private
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.homePanel_ = function() {
		this.loadHelpContent_(null, this.helpLocale_);
	};


	/**
	 * Process events that reach the wrapper element.
	 *
	 * @param {HTMLElement} context The context in which this function was called
	 * @param {Event} event The event triggered on this handler
	 */
	$.pkp.controllers.HelpPanelHandler.prototype.handleWrapperEvents =
			function(context, event) {

		// Get a reference to this handler's element as a jQuery object
		var $element = this.getHtmlElement();

		// Close click events directly on modal (background screen)
		if (event.type == 'click' && $element.is($(event.target))) {
			$element.trigger('pkp.HelpPanel.Close');
			return;
		}

		// Close for ESC keypresses (27)
		if (event.type == 'keyup' && event.which == 27) {
			$element.trigger('pkp.HelpPanel.Close');
			return;
		}
	};



/** @param {jQuery} $ jQuery closure. */
}(jQuery));
