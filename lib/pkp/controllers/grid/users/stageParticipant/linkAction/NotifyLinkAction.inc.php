<?php
/**
 * @file controllers/grid/users/stageParticipant/linkAction/NotifyLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotifyLinkAction
 * @ingroup controllers_grid_users_stageParticipant
 *
 * @brief An action to open up the notify part of the stage participants grid.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class NotifyLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $submission Submission The submission
	 * @param $stageId int
	 * @param $userId optional
	 *  to show information about.
	 */
	function __construct($request, &$submission, $stageId, $userId = null) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
		// Prepare request arguments
		$requestArgs['submissionId'] = $submission->getId();
		$requestArgs['stageId'] = $stageId;
		if ($userId) $requestArgs['userId'] = $userId;

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$router = $request->getRouter();
		$ajaxModal = new AjaxModal(
			$router->url(
				$request, null,
				'grid.users.stageParticipant.StageParticipantGridHandler', 'viewNotify',
				null, $requestArgs
			),
			__('submission.stageParticipants.notify'),
			'modal_email'
		);

		// Configure the file link action.
		parent::__construct(
			'notify', $ajaxModal,
			__('submission.stageParticipants.notify'), 'notify'
		);
	}
}

?>
