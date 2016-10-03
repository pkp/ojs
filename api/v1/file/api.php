<?php 

require_once dirname(__FILE__) . '/FileHandler.php';

$app = new \Slim\App;

// $app->get('/{contextPath}/api/{version}/file/{fileId}', '\FileHandler:getFile');

$app->get('/{contextPath}/api/{version}/file/{fileId}', function ($request, $response, $args) {
	$fileId = $request->getAttribute('fileId');
	$response->getBody()->write("Serving file with id: {$fileId}");
	return $response;
});

return  $app;