/**
 * @file js/controllers/RangeSliderHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RangeSliderHandler
 * @ingroup js_controllers
 *
 * @brief PKP range slider handler (extends the functionality of the jqueryUI
 *  range slider)
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.classes.Handler
	 *
	 * @param {jQueryObject} $rangeSliderField the wrapped HTML input element.
	 * @param {Object} options options to be passed
	 *  into the jqueryUI slider plugin.
	 */
	$.pkp.controllers.RangeSliderHandler = function($rangeSliderField, options) {
		this.parent($rangeSliderField, options);

		// Check that our required options are included
		if (!options.min || !options.max) {
			throw new Error(['The "min" and "max"',
				'settings are required in a RangeSliderHandler'].join(''));
		}

		// Get the container that will hold the actual slider.
		this.slider_ = $rangeSliderField.find(
				'.pkp_controllers_rangeSlider_slider'
				);

		// Get the container that will display the numeric values of the slider.
		this.labelMin_ = $rangeSliderField.find(
				'.pkp_controllers_rangeSlider_sliderValueMin'
				);
		this.labelMax_ = $rangeSliderField.find(
				'.pkp_controllers_rangeSlider_sliderValueMax'
				);

		// Create slider settings.
		var opt = {}, rangeSliderOptions;
		opt.min = options.min;
		opt.max = options.max;
		if (typeof options.values === 'undefined') {
			opt.values = [options.min, options.max];
		} else {
			opt.values = options.values;
		}
		rangeSliderOptions = $.extend({ },
				this.self('DEFAULT_PROPERTIES_'), opt);

		// Create the slider with the jqueryUI plug-in.
		this.slider_.slider(rangeSliderOptions);
		this.bind('slide', this.sliderAdjusted);

		// Set up the toggleable option
		if (typeof options.toggleable !== 'undefined' && options.toggleable) {
			this.toggleCheckbox_ = this.getHtmlElement().find('.toggle input');
			this.toggleCheckbox_.on('change', this.callbackWrapper(this.toggleField));
		}
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.RangeSliderHandler, $.pkp.classes.Handler);


	//
	// Private static properties
	//
	/**
	 * The container that will hold the actual slider.
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.RangeSliderHandler.slider_ = null;


	/**
	 * The container that will display the numeric min value of the slider.
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.RangeSliderHandler.labelMin_ = null;


	/**
	 * The container that will display the numeric max value of the slider.
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.RangeSliderHandler.labelMax_ = null;


	/**
	 * The checkbox that will enable/disable the field
	 * @private
	 * @type {HTMLElement}
	 */
	$.pkp.controllers.RangeSliderHandler.toggleCheckbox_ = null;


	/**
	 * Default options
	 * @private
	 * @type {Object}
	 * @const
	 */
	$.pkp.controllers.RangeSliderHandler.DEFAULT_PROPERTIES_ = {
		// General settings
		range: true
	};


	//
	// Public Methods
	//
	/**
	 * Handle event triggered by adjusting a range slider value
	 *
	 * @param {HTMLElement} rangeSliderElement The element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {Object} ui The tabs ui data.
	 */
	$.pkp.controllers.RangeSliderHandler.prototype.sliderAdjusted =
			function(rangeSliderElement, event, ui) {

		// Set the label
		var $minVal, $maxVal;
		this.labelMin_.html(ui.values[0]);
		this.labelMax_.html(ui.values[1]);

		// Set the hidden inputs
		$minVal = $(rangeSliderElement).children(
				'.pkp_controllers_rangeSlider_minInput'
				);
		$minVal.val(ui.values[0]);
		$maxVal = $(rangeSliderElement).children(
				'.pkp_controllers_rangeSlider_maxInput'
				);
		$maxVal.val(ui.values[1]);
	};


	/**
	 * Enable/disable this field
	 *
	 * @param {HTMLElement} rangeSliderElement The element that triggered
	 *  the event.
	 * @param {Event} event The triggered event.
	 * @param {Object} ui The tabs ui data.
	 */
	$.pkp.controllers.RangeSliderHandler.prototype.toggleField =
			function(rangeSliderElement, event, ui) {

		this.getHtmlElement().toggleClass('is_enabled');
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
