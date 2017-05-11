<?php

/**
 * @file controllers/grid/submissions/SubmissionsListGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileRow
 * @ingroup controllers_grid_submissions
 *
 * @brief Handle editor submission list grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class SubmissionsListGridRow extends GridRow {
	/** @var array List of user roles */
	var $_userRoles;

	/**
	 * Constructor
	 * @var $userRoles array List of available user roles
	 */
	function __construct($userRoles) {
		parent::__construct();
		$this->_userRoles = $userRoles;
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$rowId = $this->getId();

		if (!empty($rowId) && is_numeric($rowId)) {
			// 1) Delete submission action.
			$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */
			$submission = $submissionDao->getById($rowId);
			assert(is_a($submission, 'Submission'));
			if ($submission->getSubmissionProgress() != 0 || in_array(ROLE_ID_MANAGER, $this->_userRoles)) {
				$router = $request->getRouter();
				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$this->addAction(
					new LinkAction(
						'delete',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('common.confirmDelete'), __('common.delete'),
							$router->url(
								$request, null, null,
								'deleteSubmission', null, array('submissionId' => $rowId)
							),
							'modal_delete'
						),
						__('grid.action.delete'),
						'delete'
					)
				);
			}

			if (count(array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT), $this->_userRoles))) {
				// 2) Information Centre action
				import('lib.pkp.controllers.informationCenter.linkAction.SubmissionInfoCenterLinkAction');
				$this->addAction(new SubmissionInfoCenterLinkAction($request, $rowId, 'grid.action.moreInformation'));
			}
		}
	}
}

?>
