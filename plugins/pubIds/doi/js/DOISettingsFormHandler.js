/**
 * @defgroup plugins_pubIds_doi_js
 */
/**
 * @file plugins/pubIds/doi/js/DOISettingsFormHandler.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOISettingsFormHandler.js
 * @ingroup plugins_pubIds_doi_js
 *
 * @brief Handle the DOI Settings form.
 */
(function($) {

	/** @type {Object} */
	$.pkp.plugins.pubIds.doi =
			$.pkp.plugins.pubIds.doi ||
			{ js: { } };



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.AjaxFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.plugins.pubIds.doi.js.DOISettingsFormHandler =
			function($form, options) {

		this.parent($form, options);

		$(':radio, :checkbox', $form).click(
				this.callbackWrapper(this.updatePatternFormElementStatus_));
		//ping our handler to set the form's initial state.
		this.callbackWrapper(this.updatePatternFormElementStatus_());

		this.bind('formSubmitted', this.callbackWrapper(this.maybeReloadPage_));
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.plugins.pubIds.doi.js.DOISettingsFormHandler,
			$.pkp.controllers.form.AjaxFormHandler);


	/**
	 * Callback to replace the element's content.
	 *
	 * @private
	 */
	$.pkp.plugins.pubIds.doi.js.DOISettingsFormHandler.prototype.
			updatePatternFormElementStatus_ =
			function() {
		var $element = this.getHtmlElement(), pattern, $contentChoices;
		if ($('[id^="doiSuffix"]').filter(':checked').val() == 'pattern') {
			$contentChoices = $element.find(':checkbox');
			pattern = new RegExp('enable(.*)Doi');
			$contentChoices.each(function() {
				var patternCheckResult = pattern.exec($(this).attr('name')),
						$correspondingTextField = $element.find('[id*="' +
						patternCheckResult[1] + 'SuffixPattern"]').
						filter(':text');

				if (patternCheckResult !== null &&
						patternCheckResult[1] !== 'undefined') {
					if ($(this).is(':checked')) {
						$correspondingTextField.removeAttr('disabled');
					} else {
						$correspondingTextField.attr('disabled', 'disabled');
					}
				}
			});
		} else {
			$element.find('[id*="SuffixPattern"]').filter(':text').
					attr('disabled', 'disabled');
		}
	};


	/**
	* Reload the page if we're on an import/export page. The DOI settings can be accessed from several
	* import/export screens. When the DOI settings change, this can impact the import/export settings, so
	* we just reload the whole page.
	*
	* @private
	*/
	$.pkp.plugins.pubIds.doi.js.DOISettingsFormHandler.prototype.maybeReloadPage_ = function() {
		if ($('body').hasClass('pkp_op_importexport')) {
			window.location.reload();
		}
	};

/** @param {jQuery} $ jQuery closure. */
}(jQuery));
