<?php
/**
 * @file controllers/api/file/linkAction/DownloadFileLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DownloadFileLinkAction
 * @ingroup controllers_api_file_linkAction
 *
 * @brief An action to download a file.
 */

import('lib.pkp.controllers.api.file.linkAction.FileLinkAction');

class DownloadFileLinkAction extends FileLinkAction {
	/** @var string Optional label to use instead of file name */
	var $label;

	/**
	 * Constructor
	 * @param $request Request
	 * @param $submissionFile SubmissionFile the submission file to
	 *  link to.
	 * @param $stageId int (optional)
	 * @param $label string (optional) Label to use instead of filename
	 */
	function __construct($request, $submissionFile, $stageId = null, $label = null) {
		// Instantiate the redirect action request.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');
		$this->label = $label;
		$redirectRequest = new PostAndRedirectAction(
			$router->url(
				$request, null, 'api.file.FileApiHandler', 'recordDownload',
				null, $this->getActionArgs($submissionFile, $stageId)),
			$router->url(
				$request, null, 'api.file.FileApiHandler', 'downloadFile',
				null, $this->getActionArgs($submissionFile, $stageId))
		);

		// Configure the file link action.
		parent::__construct(
			'downloadFile', $redirectRequest, $this->getLabel($submissionFile),
			$submissionFile->getDocumentType(),
			$submissionFile->getFileId() . '-' . $submissionFile->getRevision()
		);
	}

	/**
	 * Get the label for the file download action.
	 * @param $submissionFile SubmissionFile
	 * @return string
	 */
	function getLabel($submissionFile) {
		if ($this->label !== null) return $this->label;
		return $submissionFile->getFileLabel();
	}
}

?>
