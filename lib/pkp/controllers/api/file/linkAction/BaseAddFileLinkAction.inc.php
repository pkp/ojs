<?php
/**
 * @defgroup controllers_api_file_linkAction Link action API controller
 */

/**
 * @file controllers/api/file/linkAction/BaseAddFileLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BaseAddFileLinkAction
 * @ingroup controllers_api_file_linkAction
 *
 * @brief Abstract base class for file upload actions.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class BaseAddFileLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $submissionId integer The submission the file should be
	 *  uploaded to.
	 * @param $stageId integer The workflow stage in which the file
	 *  uploader is being instantiated (one of the WORKFLOW_STAGE_ID_*
	 *  constants).
	 * @param $uploaderRoles array The ids of all roles allowed to upload
	 *  in the context of this action.
	 * @param $uploaderGroupIds array The ids of all allowed user groups
	 *  to upload in the context of this action, or null to permit all.
	 * @param $actionArgs array The arguments to be passed into the file
	 *  upload wizard.
	 * @param $wizardTitle string The title to be displayed in the file
	 *  upload wizard.
	 * @param $buttonLabel string The link action's button label.
	 */
	function __construct($request, $submissionId, $stageId,
			$uploaderRoles, $uploaderGroupIds, $actionArgs, $wizardTitle, $buttonLabel) {

		// Augment the action arguments array.
		$actionArgs['submissionId'] = $submissionId;
		$actionArgs['stageId'] = $stageId;
		assert(is_array($uploaderRoles) && count($uploaderRoles) >= 1);
		$actionArgs['uploaderRoles'] = implode('-', (array) $uploaderRoles);
		$actionArgs['uploaderGroupIds'] = implode('-', (array) $uploaderGroupIds);

		// Instantiate the file upload modal.
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.WizardModal');
		$modal = new WizardModal(
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'wizard.fileUpload.FileUploadWizardHandler', 'startWizard',
				null, $actionArgs
			),
			$wizardTitle, 'modal_add_file'
		);

		// Configure the link action.
		parent::__construct('addFile', $modal, $buttonLabel, 'add');
	}
}

?>
