<?php
/**
 * @file controllers/grid/settings/preparedEmails/linkAction/EditEmailLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditEmailLinkAction
 * @ingroup controllers_grid_settings_preparedEmails_linkAction
 *
 * @brief Add/Edit a prepared email.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class EditEmailLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $emailKey string
	 */
	function __construct($request, $emailKey = null) {
		// Create the action arguments array.
		$actionArgs = array();
		if($emailKey) $actionArgs['emailKey'] = $emailKey;

		// Instantiate the file upload modal.
		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$title = $emailKey ? 'manager.emails.editEmail' : 'manager.emails.addEmail';
		$action = $emailKey ? 'editPreparedEmail' : 'addPreparedEmail';
		$icon = $emailKey ? 'modal_edit' : 'modal_add_item';
		$linkIcon = $emailKey ? 'edit' : 'add_item';

		$modal = new AjaxModal(
			$dispatcher->url($request, ROUTE_COMPONENT, null,
				'grid.settings.preparedEmails.PreparedEmailsGridHandler', $action,
				null, $actionArgs),
			__($title), $icon);

		// Configure the link action.
		parent::__construct($action, $modal, __($title), $linkIcon);
	}
}

?>
