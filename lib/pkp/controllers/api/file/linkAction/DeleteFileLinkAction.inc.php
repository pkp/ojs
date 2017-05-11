<?php
/**
 * @file controllers/api/file/linkAction/DeleteFileLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DeleteFileLinkAction
 * @ingroup controllers_api_file_linkAction
 *
 * @brief An action to delete a file.
 */

import('lib.pkp.controllers.api.file.linkAction.FileLinkAction');

class DeleteFileLinkAction extends FileLinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $submissionFile SubmissionFile the submission file to be deleted
	 * @param $stageId int (optional)
	 * @param $localeKey string (optional) Locale key to use for delete link
	 *  be deleted.
	 */
	function __construct($request, $submissionFile, $stageId, $localeKey = 'grid.action.delete') {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		parent::__construct(
			'deleteFile',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('common.confirmDelete'), __('common.delete'),
				$router->url(
					$request, null, 'api.file.ManageFileApiHandler',
					'deleteFile', null, $this->getActionArgs($submissionFile, $stageId)
				),
				'modal_delete'
			),
			__($localeKey), 'delete'
		);
	}
}

?>
