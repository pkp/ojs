/**
 * @file js/controllers/form/DropdownHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DropdownHandler
 * @ingroup js_controllers_form
 *
 * @brief Handler for a container allowing the user to select from an
 *   AJAX-provided list of options, triggering an event upon selection.
 *
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $container the wrapped HTML container element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.form.DropdownHandler =
			function($container, options) {

		this.parent($container, options);

		// Save the event name to trigger upon selection for later
		this.eventName_ = options.eventName;

		// Save the default key, to select upon the first list load.
		this.defaultKey_ = options.defaultKey;

		// Expose e.g. the selectMonograph event to the containing element.
		this.publishEvent(this.eventName_);

		// Save the url for fetching the options in the dropdown element.
		this.getOptionsUrl_ = options.getOptionsUrl;

		// We're not interested in tracking changes to this subclass
		// since it usually loads content or redirects to another page.
		this.trackFormChanges = false;

		// Attach container elements events.
		$container.find('select').change(
				this.callbackWrapper(this.selectOptionHandler_));

		// Load the list of submissions.
		this.loadOptions_();

		// React to the management grid modal being closed.
		this.bind('containerReloadRequested', this.containerReloadHandler_);
	};

	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.form.DropdownHandler,
			$.pkp.classes.Handler);


	//
	// Private properties
	//
	/**
	 * The name of the event to trigger upon selection.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.eventName_ = null;


	/**
	 * The key for the default value to select upon option load.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.defaultKey_ = null;


	/**
	 * The key for the current value to select upon option load.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.currentKey_ = null;


	/**
	 * The key for the default value to select upon option load.
	 * @private
	 * @type {string?}
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.getOptionsUrl_ = null;


	//
	// Private helper methods
	//
	/**
	 * Respond to an "item selected" call by triggering a published event.
	 *
	 * @param {HTMLElement} sourceElement The element that
	 *  issued the event.
	 * @param {Event} event The triggering event.
	 * @private
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.selectOptionHandler_ =
			function(sourceElement, event) {

		// Trigger the published event.
		this.trigger(/** @type {string} */ (this.eventName_),
				[$(sourceElement).val()]);
	};


	/**
	 * Respond to an "item selected" call by triggering a published event.
	 *
	 * @private
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.loadOptions_ =
			function() {

		$.get(this.getOptionsUrl_,
				this.callbackWrapper(this.setOptionList_), 'json');
	};


	/**
	 * Set the list of available items.
	 *
	 * @param {Object} ajaxContext The AJAX request context.
	 * @param {Object} jsonData A parsed JSON response object.
	 * @private
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.setOptionList_ =
			function(ajaxContext, jsonData) {

		var processedJsonData = this.handleJson(jsonData),
				$container = this.getHtmlElement(),
				$select = $container.find('select'),
				optionId, $option;

		// For each supplied option, add it to the select menu.
		for (optionId in processedJsonData.content) {
			$option = $('<option/>');
			$option.attr('value', optionId);
			if (this.defaultKey_ == optionId || this.currentKey_ == optionId) {
				$option.attr('selected', 'selected');
				this.trigger(/** @type {string} */ (this.eventName_), [optionId]);
			}
			$option.text(processedJsonData.content[optionId]);
			$select.append($option);
		}

		this.trigger('dropDownOptionSet');
	};


	/**
	 * Handle the containerReloadRequested events triggered by the management
	 * grids for categories or series.
	 * @private
	 *
	 * @param {$.pkp.controllers.form.FormHandler} sourceElement The element
	 *  that triggered the event.
	 * @param {Event} event The event.
	 */
	$.pkp.controllers.form.DropdownHandler.prototype.containerReloadHandler_ =
			function(sourceElement, event) {

		// prune the list before reloading the items.
		var $container = this.getHtmlElement(),
				$select = $container.find('select');

		this.currentKey_ = /** @type {string} */ ($select.find('option:selected')
				.attr('value'));

		$select.find('option[value!="0"]').remove();
		this.loadOptions_();
	};
}(jQuery));
