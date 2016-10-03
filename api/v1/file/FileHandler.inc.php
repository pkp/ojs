<?php 

/**
 * @file api/v1/file/FileHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileHandler
 * @ingroup api_v1_file
 *
 * @brief Handle API requests for file operations.
 *
 */

use \Slim\App;

class FileHandler extends Handler {
	protected $container;

	/**
	 * Initialization
	 * @return App
	 */
	public static function init() {
		$app = new App;
		$app->get('/{contextPath}/api/{version}/file/{fileId}', '\FileHandler:getFile');
		return $app;
	}

	/**
	 * Constructor
	 * 
	 * @param Interop\Container\ContainerInterface $ci
	 */
	public function __construct(Interop\Container\ContainerInterface $c) {
		$this->container = $c;
	}

	/**
	 * Handle file download
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function getFile($slimRequest, $response, $args) {
		$fileId = $slimRequest->getAttribute('fileId');
		$response->getBody()->write("Serving file with id: {$fileId}");
		return $response;
	}
}
