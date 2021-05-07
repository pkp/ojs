<?php

/**
 * @file api/v1/issues/IssueHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueHandler
 * @ingroup api_v1_issues
 *
 * @brief Handle API requests for issues operations.
 *
 */

use PKP\handler\APIHandler;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\authorization\ContextAccessPolicy;

use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\core\Services;

class IssueHandler extends APIHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_handlerPath = 'issues';
        $roles = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR];
        $this->_endpoints = [
            'GET' => [
                [
                    'pattern' => $this->getEndpointPattern(),
                    'handler' => [$this, 'getMany'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/current',
                    'handler' => [$this, 'getCurrent'],
                    'roles' => $roles
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/{issueId:\d+}',
                    'handler' => [$this, 'get'],
                    'roles' => $roles
                ],
            ]
        ];
        parent::__construct();
    }

    //
    // Implement methods from PKPHandler
    //
    public function authorize($request, &$args, $roleAssignments)
    {
        $routeName = null;
        $slimRequest = $this->getSlimRequest();

        if (!is_null($slimRequest) && ($route = $slimRequest->getAttribute('route'))) {
            $routeName = $route->getName();
        }

        $this->addPolicy(new ContextRequiredPolicy($request));
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        $this->addPolicy(new OjsJournalMustPublishPolicy($request));

        if ($routeName === 'get') {
            $this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    //
    // Public handler methods
    //
    /**
     * Get a collection of issues
     *
     * @param $slimRequest Request Slim request object
     * @param $response Response object
     * @param array $args arguments
     *
     * @return Response
     */
    public function getMany($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $currentUser = $request->getUser();
        $context = $request->getContext();

        if (!$context) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $defaultParams = [
            'count' => 20,
            'offset' => 0,
        ];

        $requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

        $params = [];

        // Process query params to format incoming data as needed
        foreach ($requestParams as $param => $val) {
            switch ($param) {

                case 'orderBy':
                    if (in_array($val, ['datePublished', 'lastModified', 'seq'])) {
                        $params[$param] = $val;
                    }
                    break;

                case 'orderDirection':
                    $params[$param] = $val === 'ASC' ? $val : 'DESC';
                    break;

                // Enforce a maximum count to prevent the API from crippling the
                // server
                case 'count':
                    $params[$param] = min(100, (int) $val);
                    break;

                case 'offset':
                    $params[$param] = (int) $val;
                    break;

                // Always convert volume, number and year values to array
                case 'volumes':
                case 'volume':
                case 'numbers':
                case 'number':
                case 'years':
                case 'year':

                    // Support deprecated `year`, `number` and `volume` params
                    if (substr($param, -1) !== 's') {
                        $param .= 's';
                    }

                    if (is_string($val)) {
                        $val = explode(',', $val);
                    } elseif (!is_array($val)) {
                        $val = [$val];
                    }
                    $params[$param] = array_map('intval', $val);
                    break;

                case 'isPublished':
                    $params[$param] = $val ? true : false;
                    break;

                case 'searchPhrase':
                    $params[$param] = $val;
                    break;
            }
        }

        $params['contextId'] = $context->getId();

        \HookRegistry::call('API::issues::params', [&$params, $slimRequest]);

        // You must be a manager or site admin to access unpublished Issues
        $isAdmin = $currentUser->hasRole([ROLE_ID_MANAGER], $context->getId()) || $currentUser->hasRole([ROLE_ID_SITE_ADMIN], CONTEXT_SITE);
        if (isset($params['isPublished']) && !$params['isPublished'] && !$isAdmin) {
            return $response->withStatus(403)->withJsonError('api.submissions.403.unpublishedIssues');
        } elseif (!$isAdmin) {
            $params['isPublished'] = true;
        }

        $items = [];
        $issuesIterator = Services::get('issue')->getMany($params);
        $propertyArgs = [
            'request' => $request,
            'slimRequest' => $slimRequest,
        ];
        foreach ($issuesIterator as $issue) {
            $items[] = Services::get('issue')->getSummaryProperties($issue, $propertyArgs);
        }

        $data = [
            'itemsMax' => Services::get('issue')->getMax($params),
            'items' => $items,
        ];

        return $response->withJson($data, 200);
    }

    /**
     * Get the current issue
     *
     * @param $slimRequest Request Slim request object
     * @param $response Response object
     * @param array $args arguments
     *
     * @return Response
     */
    public function getCurrent($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        $issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
        $issue = $issueDao->getCurrent($context->getId());

        if (!$issue) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $data = Services::get('issue')->getFullProperties($issue, [
            'request' => $request,
            'slimRequest' => $slimRequest,
        ]);

        return $response->withJson($data, 200);
    }

    /**
     * Get a single issue
     *
     * @param $slimRequest Request Slim request object
     * @param $response Response object
     * @param array $args arguments
     *
     * @return Response
     */
    public function get($slimRequest, $response, $args)
    {
        $request = $this->getRequest();
        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

        if (!$issue) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $data = Services::get('issue')->getFullProperties($issue, [
            'request' => $request,
            'slimRequest' => $slimRequest,
        ]);

        return $response->withJson($data, 200);
    }
}
