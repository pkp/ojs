/**
 * @file js/controllers/grid/metadata/MetadataGridHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief Metadata grid handler.
 */
(function($) {

	// Define the namespace.
	$.pkp.controllers.grid.settings.metadata =
			$.pkp.controllers.grid.settings.metadata || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {Object} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.settings.metadata.MetadataGridHandler =
			function($grid, options) {
		$grid.find(':checkbox[name$="EnabledSubmission"]').change(
				this.callbackWrapper(this.toggleSubmissionEnabledHandler_));

		this.parent($grid, options);
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.grid.settings.metadata
			.MetadataGridHandler, $.pkp.controllers.grid.GridHandler);


	//
	// Extended methods from GridHandler
	//
	/**
	 * @inheritDoc
	 */
	$.pkp.controllers.grid.settings.metadata.MetadataGridHandler.
			prototype.initialize = function(options) {

		this.parent('initialize', options);

		// Initialize the controls with sensible readonly states
		$(this.getHtmlElement()).find(':checkbox[name$="EnabledSubmission"]')
				.change();
	};


	//
	// Private methods.
	//
	/**
	 * Callback that will be activated when the "delete" icon is clicked
	 *
	 * @private
	 *
	 * @param {Object} callingContext The calling element or object.
	 * @param {Event=} opt_event The triggering event (e.g. a click on
	 *  a button.
	 * @return {boolean} Should return false to stop event processing.
	 */
	$.pkp.controllers.grid.settings.metadata.MetadataGridHandler.prototype.
			toggleSubmissionEnabledHandler_ = function(callingContext, opt_event) {
		var $checkbox = $(callingContext), checked = $checkbox.is(':checked'),
				$grid = $(this.getHtmlElement()), name = $checkbox.attr('name'),
				$pair = $grid.find('input:checkbox[name="' +
				name.replace('EnabledSubmission', 'EnabledWorkflow' + '"]'));
		if (checked) {
			// Workflow checkbox must be enabled if submission checkbox is
			$pair.prop('checked', true);
			$pair.prop('readonly', true);
		} else {
			// Relax forcing workflow checkbox according to submission checkbox
			$pair.prop('readonly', false);
		}
		return false;
	};
/** @param {jQuery} $ jQuery closure. */
}(jQuery));
