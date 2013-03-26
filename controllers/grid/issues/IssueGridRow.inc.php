<?php

/**
 * @file controllers/grid/settings/issue/IssueGridRow.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGridRow
 * @ingroup controllers_grid_settings_issue
 *
 * @brief Handle issue grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class IssueGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function IssueGridRow() {
		parent::GridRow();
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Is this a new row or an existing row?
		$issueId = $this->getId();
		if (!empty($issueId) && is_numeric($issueId)) {
			$router =& $request->getRouter();

			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editIssue',
					new AjaxModal(
						$router->url($request, null, null, 'editIssue', null, array('issueId' => $issueId)),
						__('grid.action.edit'),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit'
				)
			);

			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'deleteIssue',
					new RemoteActionConfirmationModal(
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'deleteIssue', null, array('issueId' => $issueId)), 'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}

?>
