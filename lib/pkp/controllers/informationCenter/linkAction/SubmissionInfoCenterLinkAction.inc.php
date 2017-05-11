<?php

/**
 * @file controllers/informationCenter/linkAction/SubmissionInfoCenterLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionInfoCenterLinkAction
 * @ingroup controllers_informationCenter
 *
 * @brief An action to open up the information center for a submission.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class SubmissionInfoCenterLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $submissionId int the ID of the submission to present link for
	 * to show information about.
	 * @param $linkKey string optional locale key to display for link
	 */
	function __construct($request, $submissionId, $linkKey = 'informationCenter.editorialHistory') {
		// Instantiate the information center modal.

		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($submissionId);

		$primaryAuthor = $submission->getPrimaryAuthor();
		if (!isset($primaryAuthor)) {
			$authors = $submission->getAuthors();
			if (sizeof($authors) > 0) {
				$primaryAuthor = $authors[0];
			}
		}

		$title = (isset($primaryAuthor)) ? implode(', ', array($primaryAuthor->getLastName(), $submission->getLocalizedTitle())) : $submission->getLocalizedTitle();

		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$ajaxModal = new AjaxModal(
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'informationCenter.SubmissionInformationCenterHandler',
				'viewInformationCenter',
				null,
				array('submissionId' => $submissionId)
			),
			$title,
			'modal_information'
		);

		// Configure the link action.
		parent::__construct(
			'editorialHistory', $ajaxModal,
			__($linkKey), 'more_info'
		);
	}
}

?>
