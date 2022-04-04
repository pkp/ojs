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

use APP\facades\Repo;
use APP\issue\Collector;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use PKP\db\DAORegistry;
use PKP\handler\APIHandler;
use PKP\plugins\HookRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Role;
use PKP\security\UserGroupDAO;

class IssueHandler extends APIHandler
{
    /** @var int The default number of issues to return in one request */
    public const DEFAULT_COUNT = 20;

    /** @var int The maximum number of issues to return in one request */
    public const MAX_COUNT = 100;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_handlerPath = 'issues';
        $roles = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_REVIEWER, Role::ROLE_ID_AUTHOR];
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
     * @param Request $slimRequest Slim request object
     * @param Response $response object
     * @param array $args arguments
     *
     * @return Response
     */
    public function getMany($slimRequest, $response, $args)
    {
        $collector = Repo::issue()->getCollector()
            ->limit(self::DEFAULT_COUNT)
            ->offset(0);

        $request = $this->getRequest();
        $currentUser = $request->getUser();
        /** @var \PKP\context\Context $context */
        $context = $request->getContext();

        if (!$context) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        // Process query params to format incoming data as needed
        foreach ($slimRequest->getQueryParams() as $param => $val) {
            switch ($param) {

                case 'orderBy':
                    if (in_array($val, [Collector::ORDERBY_DATE_PUBLISHED, Collector::ORDERBY_LAST_MODIFIED, Collector::ORDERBY_SEQUENCE])) {
                        $collector->orderBy($val);
                    }
                    break;

                // Enforce a maximum count to prevent the API from crippling the
                // server
                case 'count':
                    $collector->limit(min((int) $val, self::MAX_COUNT));
                    break;

                case 'offset':
                    $collector->offset((int) $val);
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
                    $values = array_map('intval', $val);
                    switch ($param) {
                        case 'volumes':
                            $collector->filterByVolumes($values);
                            break;
                        case 'numbers':
                            $collector->filterByNumbers($values);
                            break;
                        case 'years':
                            $collector->filterByYears($values);
                            break;
                    }

                    break;

                case 'isPublished':
                    $collector->filterByPublished((bool) $val);
                    break;

                case 'searchPhrase':
                    $collector->searchPhrase($val);
                    break;
                case 'doiStatus':
                    $collector->filterByDoiStatuses(array_map('intval', $this->paramToArray($val)));
                    break;
                case 'hasDois':
                    $collector->filterByHasDois((bool) $val, $context->getEnabledDoiTypes());
            }
        }

        $collector->filterByContextIds([$context->getId()]);

        HookRegistry::call('API::issues::params', [&$collector, $slimRequest]);

        // You must be a manager or site admin to access unpublished Issues
        $isAdmin = $currentUser->hasRole([Role::ROLE_ID_MANAGER], $context->getId()) || $currentUser->hasRole([Role::ROLE_ID_SITE_ADMIN], \PKP\core\PKPApplication::CONTEXT_SITE);
        if (isset($collector->isPublished) && !$collector->isPublished && !$isAdmin) {
            return $response->withStatus(403)->withJsonError('api.submissions.403.unpublishedIssues');
        } elseif (!$isAdmin) {
            $collector->filterByPublished(true);
        }


        $issues = Repo::issue()->getMany($collector);

        return $response->withJson([
            'items' => iterator_to_array(Repo::issue()->getSchemaMap()->summarizeMany($issues, $context), false),
            'itemsMax' => Repo::issue()->getCount($collector->limit(null)->offset(null)),
        ], 200);
    }

    /**
     * Get the current issue
     *
     * @param Request $slimRequest Slim request object
     * @param Response $response object
     * @param array $args arguments
     *
     * @return Response
     */
    public function getCurrent($slimRequest, $response, $args)
    {
        $context = $this->getRequest()->getContext();

        $issue = Repo::issue()->getCurrent($context->getId());

        if (!$issue) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $data = Repo::issue()->getSchemaMap()->map(
            $issue,
            $context,
            $this->getUserGroups($context->getId()),
            $this->getGenres($context->getId())
        );

        return $response->withJson($data, 200);
    }

    /**
     * Get a single issue
     *
     * @param Request $slimRequest Slim request object
     * @param Response $response object
     * @param array $args arguments
     *
     * @return Response
     */
    public function get($slimRequest, $response, $args)
    {
        $context = $this->getRequest()->getContext();

        $issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

        if (!$issue) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $data = Repo::issue()->getSchemaMap()->map(
            $issue,
            $context,
            $this->getUserGroups($context->getId()),
            $this->getGenres($context->getId())
        );

        return $response->withJson($data, 200);
    }

    protected function getUserGroups(int $contextId): array
    {
        /** @var UserGroupDAO $userGroupDao */
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        return $userGroupDao->getByContextId($contextId)->toArray();
    }

    protected function getGenres(int $contextId): array
    {
        /** @var GenreDAO $genreDao */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        return $genreDao->getByContextId($contextId)->toArray();
    }
}
