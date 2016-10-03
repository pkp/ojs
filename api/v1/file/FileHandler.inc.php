<?php 

use \Slim\App;

class FileHandler {
	
	protected $container;
	
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
	 * @param $request request object
	 * @param $response response object
	 * @param array $args arguments
	 */
	public function getFile($request, $response, $args) {
		$fileId = $request->getAttribute('fileId');
		$response->getBody()->write("Serving file with id: {$fileId}");
		return $response;
	}
	
	/**
	 * Initialization
	 */
	public static function init() {
		$app = new App;
		$app->get('/{contextPath}/api/{version}/file/{fileId}', '\FileHandler:getFile');
		return  $app;
	}
	 
}
