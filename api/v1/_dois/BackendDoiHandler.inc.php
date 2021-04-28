<?php

/**
 * @file api/v1/_dois/BackendDoiHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BackendDoiHandler
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

use APP\facades\Repo;
use PKP\core\APIResponse;
use PKP\handler\APIHandler;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

use Slim\Http\Request as SlimRequest;

class BackendDoiHandler extends APIHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_handlerPath = '_dois';
        $this->_endpoints = array_merge_recursive($this->_endpoints, [
            'PUT' => [
                [
                    'pattern' => $this->getEndpointPattern() . "/issues/{issueId:\d+}",
                    'handler' => [$this, 'editIssue'],
                    'roles' => [Role::ROLE_ID_MANAGER],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . "/galleys/{galleyId:\d+}",
                    'handler' => [$this, 'editGalley'],
                    'roles' => [Role::ROLE_ID_MANAGER],
                ]
            ]
        ]);
        parent::__construct();
    }

    /**
     * @param \PKP\handler\Request $request
     * @param array $args
     * @param array $roleAssignments
     *
     * @return bool
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @throws Exception
     */
    public function editGalley(SlimRequest $slimRequest, APIResponse $response, array $args): \Slim\Http\Response
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        if (!$context) {
            throw new Exception('You cannot edit a Galley without sending a request PKP\core\APIResponse');
        }

        /** @var \APP\services\GalleyService $galleyService */
        $galleyService = Services::get('galley');

        $galley = $galleyService->get($args['galleyId']);
        if (!$galley) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_GALLEY, $slimRequest->getParsedBody());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        $primaryLocale = $context->getPrimaryLocale();
        $allowedLocales = $context->getSupportedFormLocales();

        $errors = $galleyService->validate(VALIDATE_ACTION_EDIT, ['doiId' => $doi->getId()], $allowedLocales, $primaryLocale);
        if (!empty($errors)) {
            return $response->withStatus(400)->withJson($errors);
        }

        $galley = $galleyService->edit($galley, ['doiId' => $doi->getId()], $request);
        $galleyProps = $galleyService->getFullProperties($galley, [
            'request' => $request
        ]);
        return $response->withJson($galleyProps, 200);
    }

    /**
     * @throws Exception
     */
    public function editIssue(SlimRequest $slimRequest, APIResponse $response, array $args): \Slim\Http\Response
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        if (!$context) {
            throw new Exception('You cannot edit a Galley without sending a request PKP\core\APIResponse');
        }

        $issue = Repo::issue()->get($args['issueId']);
        if (!$issue) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_ISSUE, $slimRequest->getParsedBody());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        $allowedLocales = $context->getSupportedFormLocales();
        $primaryLocale = $context->getPrimaryLocale();

        $errors = Repo::issue()->validate($issue, ['doiId' => $doi->getId()], $allowedLocales, $primaryLocale);
        if (!empty($errors)) {
            return $response->withStatus(400)->withJson($errors);
        }

        Repo::issue()->edit($issue, ['doiId' => $doi->getId()]);
        $issue = Repo::issue()->get($issue->getId());

        return $response->withJson(Repo::issue()->getSchemaMap()->map($issue));
    }
}
