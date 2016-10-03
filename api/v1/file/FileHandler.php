<?php 



class FileHandler {
	protected $ci;
	//Constructor
	public function __construct(Interop\Container\ContainerInterface $ci) {
		$this->ci = $ci;
	}
	 
	public function getFile($request, $response, $args) {
		$fileId = $request->getAttribute('fileId');
		$response->getBody()->write("Serving file with id: {$fileId}");
		return $response;
	}
	 
}
