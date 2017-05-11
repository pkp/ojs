<?php
/**
 * @file controllers/api/file/linkAction/DownloadLibraryFileLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DownloadLibraryFileLinkAction
 * @ingroup controllers_api_file_linkAction
 *
 * @brief An action to download a library file.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class DownloadLibraryFileLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 * @param $libraryFile LibraryFile the library file to
	 *  link to.
	 */
	function __construct($request, $libraryFile) {
		// Instantiate the redirect action request.
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.PostAndRedirectAction');
		$redirectRequest = new PostAndRedirectAction(
			$router->url(
				$request, null, 'api.file.FileApiHandler', 'enableLinkAction',
				null, $this->getActionArgs($libraryFile)),
			$router->url(
				$request, null, 'api.file.FileApiHandler', 'downloadLibraryFile',
				null, $this->getActionArgs($libraryFile))
		);

		// Configure the file link action.
		parent::__construct(
			'downloadFile', $redirectRequest, $libraryFile->getLocalizedName(),
			$libraryFile->getDocumentType()
		);
	}

	/**
	 * Return the action arguments to address a file.
	 * @param $libraryFile LibraryFile
	 * @return array
	 */
	function getActionArgs(&$libraryFile) {
		assert(is_a($libraryFile, 'LibraryFile'));

		// Create the action arguments array.
		$args = array('libraryFileId' => $libraryFile->getId());

		if ($libraryFile->getSubmissionId()) {
			$args['submissionId'] = $libraryFile->getSubmissionId();
		}

		return $args;
	}
}

?>
