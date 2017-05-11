/**
 * @file js/controllers/AutocompleteHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AutocompleteHandler
 * @ingroup js_controllers
 *
 * @brief PKP autocomplete handler (extends the functionality of the
 *  jqueryUI autocomplete)
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $autocompleteField the wrapped HTML input element.
	 * @param {Object} options options to be passed
	 *  into the jqueryUI autocomplete plugin.
	 */
	$.pkp.controllers.AutocompleteHandler = function($autocompleteField, options) {
		var autocompleteOptions, opt;

		this.parent($autocompleteField, options);

		// Get the URL passed in
		this.sourceUrl_ = options.sourceUrl;
		options.sourceUrl = undefined;

		// Get the label passed in
		this.jLabelText_ = options.jLabelText;

		this.disableSync_ = options.disableSync;

		// Create autocomplete settings.
		opt = {};
		opt.source = this.callbackWrapper(this.fetchAutocomplete);

		// Set the anchor where to append the suggestions menu
		opt.appendTo = '#' + $autocompleteField.attr('id');

		autocompleteOptions = $.extend({ },
				this.self('DEFAULT_PROPERTIES_'), opt, options);

		// Get the text input inside of this Div.
		this.textInput = $autocompleteField.find(':text');
		if (!this.disableSync_) {
			this.textInput.keyup(this.callbackWrapper(this.synchronizeFields_));
		}
		// Create the autocomplete field with the jqueryUI plug-in.
		this.textInput.autocomplete(autocompleteOptions);

		// Assign our title text to our label. We assign and then
		// clear or else the title value is displayed as the validation
		// message.
		this.textInput.attr('title', this.jLabelText_);
		$('#' + this.textInput.attr('id')).jLabel();
		this.textInput.attr('title', '');

		// Get the new label inside of this Div.
		this.textLabel_ = $autocompleteField.find('label');

		// Get the text input inside of this Div.
		this.hiddenInput_ = $autocompleteField.find('input:hidden');

		this.bind('autocompleteselect', this.itemSelected);
		this.bind('autocompletefocus', this.itemFocused);
		this.textInput.blur(this.callbackWrapper(this.textInputBlurHandler_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.AutocompleteHandler, $.pkp.classes.Handler);


	//
	// Private static properties
	//
	/**
	 * The label inside the autocomplete div that is created by jLabel.
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.AutocompleteHandler.textLabel_ = null;


	/**
	 * The label to be included as the default term for jLabel.
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.AutocompleteHandler.jLabelText_ = null;


	/**
	 * Whether or not to disable syncing the text and hidden field.
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.AutocompleteHandler.disableSync_ = null;


	/**
	 * Default options
	 * @private
	 * @type {Object}
	 * @const
	 */
	$.pkp.controllers.AutocompleteHandler.DEFAULT_PROPERTIES_ = {
		// General settings
		minLength: 2
	};


	//
	// Private properties
	//
	/**
	 * The hidden input inside the autocomplete div that holds the value.
	 * @private
	 * @type {jQueryObject}
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.hiddenInput_ = null;


	/**
	 * The URL to be used for autocomplete
	 * @private
	 * @type {?string}
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.sourceUrl_ = null;


	//
	// Protected properties
	//
	/**
	 * The text input inside the autocomplete div that holds the label.
	 * @protected
	 * @type {jQueryObject}
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.textInput = null;


	//
	// Public Methods
	//
	/**
	 * Handle event triggered by selecting an autocomplete item
	 *
	 * @param {HTMLElement} autocompleteElement The element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {Object} ui The tabs ui data.
	 * @return {boolean} Return value to be passed back
	 *  to jQuery.
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.itemSelected =
			function(autocompleteElement, event, ui) {
		var $hiddenInput, $textInput, $textLabel;

		$hiddenInput = this.hiddenInput_;
		$textInput = this.textInput;
		$textLabel = this.textLabel_;

		// only update the text field if the item has a value
		// this allows us to return a 'no items' label with
		// an empty value.

		if (ui.item.value !== '') {
			$hiddenInput.val(ui.item.value);
			$textInput.val(ui.item.label);
			// Let jLabel know that we have set a value.
			$textInput.trigger('keyup');
		}
		return false;
	};


	/**
	 * Handle event triggered by moving through the autocomplete suggestions.
	 * The default behaviour is to insert the value in the text field.  This
	 * handler inserts the label instead.
	 *
	 * @param {HTMLElement} autocompleteElement The element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {Object} ui The tabs ui data.
	 * @return {boolean} Return value to be passed back
	 *  to jQuery.
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.itemFocused =
			function(autocompleteElement, event, ui) {
		var $textInput;

		$textInput = this.textInput;

		if (ui.item.value !== '') {
			$textInput.val(ui.item.label);
		}
		return false;
	};


	/**
	 * Fetch autocomplete results.
	 * @param {HTMLElement} callingElement The calling HTML element.
	 * @param {Object} request The autocomplete search request.
	 * @param {Function} response The response handler function.
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.fetchAutocomplete =
			function(callingElement, request, response) {
		var $textInput;

		$textInput = this.textInput;
		$textInput.addClass('spinner');
		$.post(this.getAutocompleteUrl(), { term: request.term }, function(data) {
			$textInput.removeClass('spinner');
			response(data.content);
		}, 'json');
	};


	/**
	 * Get the autocomplete Url
	 * @return {?string} Autocomplete URL.
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.getAutocompleteUrl =
			function() {
		return this.sourceUrl_;
	};


	/**
	 * Set the autocomplete url
	 * @param {string} url Autocomplete URL.
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.setAutocompleteUrl =
			function(url) {
		this.sourceUrl_ = url;
	};


	//
	// Private methods.
	//
	/**
	 * Text input element blur handler.
	 * @param {HTMLElement} autocompleteElement The element that triggered
	 *  the event.
	 * @param {Event} event The blur event.
	 * @param {Object} ui UI.
	 * @private
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.textInputBlurHandler_ =
			function(autocompleteElement, event, ui) {
		// Make sure we clean the text input if user selected no option
		// from the available ones but leaved some text behind. This
		// is needed to avoid bad form validation and to make it clear to
		// users that they need to select an option.
		if (this.hiddenInput_.val() === '') {
			this.textInput.val('');
		}
	};


	/**
	 * Synchronize the input field and the hidden field.
	 * @private
	 */
	$.pkp.controllers.AutocompleteHandler.prototype.synchronizeFields_ =
			function() {
		this.hiddenInput_.val(String(this.textInput.val()));
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
