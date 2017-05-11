<?php

/**
 * @file controllers/api/task/SendReminderLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SendReminderLinkAction
 * @ingroup controllers_api_task
 *
 * @brief An action to open up a modal to send a reminder to users assigned to a task.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class SendReminderLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $actionArgs array The action arguments.
	 */
	function __construct($request, $modalTitle, $actionArgs) {
		// Instantiate the send review modal.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$ajaxModal = new AjaxModal(
			$router->url($request, null, null, 'editReminder', null, $actionArgs),
			__($modalTitle),
			'review_reminder'
		);

		// Configure the link action.
		parent::__construct(
			'sendReminder',
			$ajaxModal,
			__('editor.review.sendReminder'),
			'overdue'
		);
	}
}

?>
