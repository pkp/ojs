<?php
/**
 * @defgroup controllers_grid_files_fileList_linkAction File List Link Actions
 */

/**
 * @file controllers/grid/files/fileList/linkAction/DownloadAllLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DownloadAllLinkAction
 * @ingroup controllers_grid_files_fileList_linkAction
 *
 * @brief An action to download all files in a submission file grid.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class DownloadAllLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $actionArgs array
	 * @param $files array Files to be downloaded.
	 */
	function __construct($request, $actionArgs, $files) {
		// Instantiate the redirect action request.
		$router = $request->getRouter();
		$filesIdsAndRevisions = $this->_getFilesIdsAndRevisions($files);
		$actionArgs['filesIdsAndRevisions'] = $filesIdsAndRevisions;
		import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');
		$redirectRequest = new PostAndRedirectAction($router->url($request, null, 'api.file.FileApiHandler', 'recordDownload', null, $actionArgs),
			$router->url($request, null, 'api.file.FileApiHandler', 'downloadAllFiles', null, $actionArgs));

		// Configure the link action.
		parent::__construct('downloadAll', $redirectRequest, __('submission.files.downloadAll'), 'getPackage');
	}


	//
	// Private helper methods.
	//
	/**
	 * Return an string with all files ids and revisions.
	 * @param $files array The files that will be downloaded.
	 * @return string
	 */
	function _getFilesIdsAndRevisions($files) {
		$filesIdsAndRevisions = null;
		foreach ($files as $fileData) {
			$file =& $fileData['submissionFile'];
			$fileId = $file->getFileId();
			$revision = $file->getRevision();
			$filesIdsAndRevisions .= $fileId . '-' . $revision . ';';
			unset($file);
		}

		return $filesIdsAndRevisions;
	}
}

?>
