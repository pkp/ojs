<?php

/**
 * @file controllers/grid/submissions/exportableSubmissions/ExportableSubmissionsGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileRow
 * @ingroup controllers_grid_submissions_exportableSubmissions
 *
 * @brief Handle export submission list grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class ExportableSubmissionsGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$rowId = $this->getId();

		if (!empty($rowId) && is_numeric($rowId)) {
			// 1) Delete submission action.
			$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */
			$submission = $submissionDao->getById($rowId);
			assert(is_a($submission, 'Submission'));
			if ($request->getUserVar('hideSelectColumn')) {
				$dispatcher = $request->getDispatcher();
				$pluginName = $request->getUserVar('pluginName');
				assert(!empty($pluginName));
				$url = $dispatcher->url($request, ROUTE_PAGE, null, 'management', 'importexport', array('plugin', $pluginName, 'export'), array('selectedSubmissions[]' => $rowId));
				import('lib.pkp.classes.linkAction.request.RedirectAction');
				$redirectAction = new RedirectAction($url);
				$this->addAction(new LinkAction('export', $redirectAction, __('grid.action.export'), 'export'));
			}

			// 2) Information Centre action
			import('lib.pkp.controllers.informationCenter.linkAction.SubmissionInfoCenterLinkAction');
			$this->addAction(new SubmissionInfoCenterLinkAction($request, $rowId, 'grid.action.moreInformation'));
		}
	}
}

?>
