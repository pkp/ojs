/**
 * @defgroup js_controllers_grid
 */
/**
 * @file js/controllers/grid/issues/FutureIssueGridHandler.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FutureIssueGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief A subclass of the GridHandler for the grid displaying future issues.
 *   It handles communication between the future and back issue grids, so that
 *   updates to published issues are synchronized.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {{features}} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.issues.FutureIssueGridHandler =
			function($grid, options) {
		this.parent($grid, options);

		this.bindGlobal('issueUnpublished', this.refreshGridHandler);
	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.issues.FutureIssueGridHandler,
			$.pkp.controllers.grid.GridHandler);




}(jQuery));
