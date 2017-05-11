/**
 * @defgroup js_lib_jquery_plugins
 */

/**
 * @file js/lib/jquery/plugins/jquery.pkp.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup js_lib_jquery_plugins
 *
 * @brief PKP jQuery extensions.
 */

(function($) {


	/**
	 * Handler plug-in.
	 * @this {jQuery}
	 * @param {string} handlerName The handler to be instantiated
	 *  and attached to the target HTML element(s).
	 * @param {Object=} options Parameters to be passed on
	 *  to the handler.
	 * @return {jQueryObject} Selected HTML elements for chaining.
	 */
	$.fn.pkpHandler = function(handlerName, options) {
		// Go through all selected elements.
		this.each(function() {
			var $element = $(this);

			// Instantiate the handler and bind it
			// to the element.
			options = options || {};
			var handler = $.pkp.classes.Helper.objectFactory(
					handlerName, [$element, options]);
		});

		// Allow chaining.
		return this;
	};


	/**
	 * Re-implementation of jQuery's html() method
	 * with a remote source.
	 * @param {string} url the AJAX endpoint from which to
	 *  retrieve the HTML to be inserted.
	 * @param {Object=} callback function to be called on ajax success.
	 * @return {jQueryObject} Selected HTML elements for chaining.
	 */
	$.fn.pkpAjaxHtml = function(url, callback) {
		var $element = this.first();
		// using $.ajax instead of .getJSON to handle failures.
		// .getJSON does not allow for an error callback
		// this changes with jQuery 1.5
		$.ajax({
			url: url,
			dataType: 'json',
			success: function(jsonData) {
				$element.find('#loading').hide();
				if (jsonData.status === true) {
					// Replace the element content with
					// the remote content.
					if (jsonData.content) {
						$element.html(jsonData.content);
					}
					if (callback) {
						callback();
					}
				} else {
					// Alert that the remote call failed.
					$element.trigger('ajaxHtmlError', jsonData.content)
					alert(jsonData.content);
				}
			},
			error: function() {
				alert('Failed Ajax request or invalid JSON returned.');
			}
		});
		$element.html("<div id='loading' class='throbber'></div>");
		return this;
	};


/** @param {jQuery} $ jQuery closure. */
})(jQuery);
