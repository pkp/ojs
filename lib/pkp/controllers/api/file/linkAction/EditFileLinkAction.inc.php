<?php
/**
 * @file controllers/api/file/linkAction/EditFileLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditFileLinkAction
 * @ingroup controllers_api_file_linkAction
 *
 * @brief An action to edit a file's metadata.
 */

import('lib.pkp.controllers.api.file.linkAction.FileLinkAction');

class EditFileLinkAction extends FileLinkAction {
	/**
	 * Constructor
	 * @param $request Request
	 * @param $submissionFile SubmissionFile the submission file to edit.
	 * @param $stageId int Stage ID
	 */
	function __construct($request, $submissionFile, $stageId) {
		// Instantiate the AJAX modal request.
		$router = $request->getRouter();
		$dispatcher = $router->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$modal = new AjaxModal(
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'api.file.ManageFileApiHandler',
				'editMetadata', null,
				$this->getActionArgs($submissionFile, $stageId)
			),
			__('grid.action.editFile'),
			'modal_information'
		);

		// Configure the file link action.
		parent::__construct(
			'editFile', $modal, __('common.edit'), 'edit'
		);
	}
}

?>
