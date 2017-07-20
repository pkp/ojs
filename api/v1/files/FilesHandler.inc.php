<?php 

/**
 * @file api/v1/files/FilesHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesHandler
 * @ingroup api_v1_files
 *
 * @brief Handle API requests for files operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class FilesHandler extends APIHandler {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'files';
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
					array(
						'pattern' => $this->getEndpointPattern() . '/{fileId}',
						'handler' => array($this,'getFile'),
						'roles' => $roles
					),
				)
		);
		parent::__construct();
	}
	
	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		$routeName = null;
		$slimRequest = $this->getSlimRequest();
	
		if (!is_null($slimRequest) && ($route = $slimRequest->getAttribute('route'))) {
			$routeName = $route->getName();
		}
		
		import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy');
		$this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SUBMISSION_FILE_ACCESS_READ));
		
		return parent::authorize($request, $args, $roleAssignments);
	}
	
	//
	// Public handler methods
	//
	/**
	 * Handle file download
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function getFile($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$submissionFile = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
		assert($submissionFile); // Should have been validated already
		$context = $request->getContext();
		import('lib.pkp.classes.file.SubmissionFileManager');
		$fileManager = new SubmissionFileManager($context->getId(), $submissionFile->getSubmissionId());
		if (!$fileManager->downloadFile($submissionFile->getFileId(), $submissionFile->getRevision(), false, $submissionFile->getClientFileName())) {
			error_log('FileApiHandler: File ' . $submissionFile->getFilePath() . ' does not exist or is not readable!');
			header('HTTP/1.0 500 Internal Server Error');
			fatalError('500 Internal Server Error');
		}
	}
}