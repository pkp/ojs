/**
 * @file js/controllers/grid/users/stageParticipant/StageParticipantGridHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief Stage participant grid handler.
 */
(function($) {

	/** @type {Object} */
	$.pkp.controllers.grid.users.stageParticipant =
			$.pkp.controllers.grid.users.stageParticipant || {};



	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.CategoryGridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {Object} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.users.stageParticipant.StageParticipantGridHandler =
			function($grid, options) {
		this.parent($grid, options);

		// Reload any editorial actions on the page.
		this.bind('dataChanged', function() {
			this.refreshGridHandler();
			$(['#submissionEditorDecisionsDiv',
				'#copyeditingEditorDecisionsDiv',
				'#reviewDecisionsDiv-13'].join(','))
					.each(function() {
						$.pkp.classes.Handler.getHandler($(this)).reload();
					});
		});
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.users.stageParticipant.StageParticipantGridHandler,
			$.pkp.controllers.grid.CategoryGridHandler);


}(jQuery));
