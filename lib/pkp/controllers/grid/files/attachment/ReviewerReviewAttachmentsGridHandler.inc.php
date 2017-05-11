<?php
/**
 * @filecontrollers/grid/files/attachment/ReviewerReviewAttachmentsGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReviewAttachmentsGridHandler
 * @ingroup controllers_grid_files_attachment
 *
 * @brief Handle file grid requests.
 */

import('lib.pkp.controllers.grid.files.fileList.FileListGridHandler');

class ReviewerReviewAttachmentsGridHandler extends FileListGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.attachment.ReviewerReviewAttachmentGridDataProvider');
		// Pass in null stageId to be set in initialize from request var.
		parent::__construct(
			new ReviewerReviewAttachmentGridDataProvider(SUBMISSION_FILE_REVIEW_ATTACHMENT),
			null,
			FILE_GRID_ADD|FILE_GRID_DELETE|FILE_GRID_EDIT
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_REVIEWER),
			array(
				'fetchGrid', 'fetchRow'
			)
		);

		// Set the grid title.
		$this->setTitle('reviewer.submission.reviewerFiles');
	}

	/**
	 * @copydoc FileListGridHandler::initialize()
	 */
	function initialize($request) {
		// Watch for flag from including template to warn about the
		// review already being complete. If so, remove some capabilities.
		$capabilities = $this->getCapabilities();
		if ($request->getUserVar('reviewIsComplete')) {
			$capabilities->setCanAdd(false);
			$capabilities->setCanDelete(false);
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_REVIEWER);

		parent::initialize($request);
	}
}

?>
