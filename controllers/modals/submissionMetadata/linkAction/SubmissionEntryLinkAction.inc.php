<?php
/**
 * @file controllers/modals/submissionMetadata/linkAction/SubmissionEntryLinkAction.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEntryLinkAction
 * @ingroup controllers_modals_submissionMetadata_linkAction
 *
 * @brief An action to open a modal to display metadata relevant to the article and its galleys.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class SubmissionEntryLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $submissionId integer The submission to show meta-data for.
	 * @param $stageId integer The stage ID of the viewer's context
	 * @param $selectedGalleyId integer The galley ID that
	 * will be used to open the correspondent galley tab. If
	 * none is passed, the first submission entry tab will be opened.
	 * @param $image string
	 */
	function __construct($request, $submissionId, $stageId, $selectedGalleyId = null, $image = 'information') {
		// Instantiate the modal.
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		$actionArgs = array();
		$actionArgs['submissionId'] = $submissionId;
		$actionArgs['stageId'] = $stageId;
		if ($selectedGalleyId) {
			$actionArgs['selectedGalleyId'] = $selectedGalleyId;
		}

		$modal = new AjaxModal(
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'modals.submissionMetadata.IssueEntryHandler',
				'fetch', null,
				$actionArgs
			),
			__('submission.issueEntry.modalTitle'),
			'modal_more_info'
		);

		// Configure the link action.
		$toolTip = ($image == 'completed') ? __('grid.action.galleyInIssueEntry') : null;
		parent::__construct('issueEntry', $modal, __('submission.issueEntry'), $image, $toolTip);
	}
}


