<?php

/**
 * @file controllers/grid/issues/IssueGridRow.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGridRow
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issue grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class IssueGridRow extends GridRow {

	//
	// Overridden template methods
	//
	/*
	 * @copydoc GridRow::initialize
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$issueId = $this->getId();
		if (!empty($issueId) && is_numeric($issueId)) {
			$issue = $this->getData();
			assert(is_a($issue, 'Issue'));
			$router = $request->getRouter();

			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editIssue', null, array('issueId' => $issueId)),
						__('editor.issues.editIssue', array('issueIdentification' => $issue->getIssueIdentification())),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit'
				)
			);

			import('lib.pkp.classes.linkAction.request.OpenWindowAction');
			$dispatcher = $request->getDispatcher();
			$this->addAction(
				new LinkAction(
					$issue->getDatePublished()?'viewIssue':'previewIssue',
					new OpenWindowAction(
						$dispatcher->url($request, ROUTE_PAGE, null, 'issue', 'view', array($issueId))
					),
					__($issue->getDatePublished()?'grid.action.viewIssue':'grid.action.previewIssue'),
					'information'
				)
			);

			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			if ($issue->getDatePublished()) {
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				$this->addAction(
					new LinkAction(
						'unpublish',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('editor.issues.confirmUnpublish'),
							__('editor.issues.unpublishIssue'),
							$router->url($request, null, null, 'unpublishIssue', null, array('issueId' => $issueId)),
							'modal_delete'
						),
						__('editor.issues.unpublishIssue'),
						'delete'
					)
				);
			} else {
				$this->addAction(
					new LinkAction(
						'publish',
						new AjaxModal(
							$router->url(
								$request, null, null, 'publishIssue', null, array('issueId' => $issueId)),
								__('editor.issues.publishIssue'),
								'modal_confirm'
							),
						__('editor.issues.publishIssue'),
						'advance'
					)
				);
			}

			if ($issue->getDatePublished() && !$issue->getCurrent()) {
				$this->addAction(
					new LinkAction(
						'setCurrentIssue',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('editor.issues.confirmSetCurrentIssue'),
							__('editor.issues.currentIssue'),
							$router->url($request, null, null, 'setCurrentIssue', null, array('issueId' => $issueId)),
							'modal_delete'
						),
						__('editor.issues.currentIssue'),
						'delete'
					)
				);
			}

			$this->addAction(
				new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'deleteIssue', null, array('issueId' => $issueId)),
						'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}


