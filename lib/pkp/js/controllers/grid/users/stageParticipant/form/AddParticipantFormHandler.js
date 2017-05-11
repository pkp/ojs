/**
 * @defgroup js_controllers_grid_users_stageParticipant_form
 */
/**
 * @file js/controllers/grid/users/stageParticipant/form/AddParticipantFormHandler.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AddParticipantFormHandler
 * @ingroup js_controllers_grid_users_stageParticipant_form
 *
 * @brief Handle the search user filter and
 * add the value to the hidden userGroupId field.
 */
(function($) {


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.form.ClientFormHandler
	 *
	 * @param {jQueryObject} $form the wrapped HTML form element.
	 * @param {Object} options form options.
	 */
	$.pkp.controllers.grid.users.stageParticipant.form.AddParticipantFormHandler =
			function($form, options) {

		this.parent($form, options);

		$('select[name^=\'filterUserGroupId\']', $form).change(
				this.callbackWrapper(this.addUserGroupId));

		// initially populate the input field.
		this.addUserGroupId();

	};
	$.pkp.classes.Helper.inherits(
			$.pkp.controllers.grid.users.stageParticipant.form.
					AddParticipantFormHandler,
			$.pkp.controllers.form.ClientFormHandler);


	//
	// Public methods
	//
	/**
	 * Method to add the value to the hidden userGroupId field
	 */
	$.pkp.controllers.grid.users.stageParticipant.form.AddParticipantFormHandler.
			prototype.addUserGroupId = function() {

		var $form = this.getHtmlElement(),
				$filterUserGroupId = $form.find('select[name^=\'filterUserGroupId\']'),
				filterUserGroupIdVal = /** @type {string} */ $filterUserGroupId.val();

		$('input[name=\'userGroupId\']').val(filterUserGroupIdVal);
	};


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
