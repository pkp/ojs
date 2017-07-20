<?php 

/**
 * @file api/v1/files/GalleysHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleysHandler
 * @ingroup api_v1_galleys
 *
 * @brief Handle API requests for galleys operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class GalleysHandler extends APIHandler {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'galleys';
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern() . '/{representationId}',
					'handler' => array($this,'getGalley'),
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
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		
		import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
		$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		
		return parent::authorize($request, $args, $roleAssignments);
	}
	
	//
	// Public handler methods
	//
	/**
	 * Handle galley fetch by redirecting to file download
	 * 
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * 
	 * @return Response
	 */
	public function getGalley($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$galley = $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION);
		assert($galley);
		
		$contextPath = $this->getParameter('contextPath');
		$version = $this->getParameter('version');
		$fileId = $galley->getFileId();
		$submissionFile = $galley->getFile();
		$urlData = array(
			'submissionId' 	=> $this->getParameter('submissionId'),
			'revision'		=> $submissionFile->getRevision(),
		);
		
		$uri = "/{$contextPath}/api/{$version}/files/{$fileId}?" . http_build_query($urlData);
		
		return $response->withStatus(302)->withHeader('Location', $uri);
	}
}