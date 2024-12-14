<?php

/**
 * @file api/v1/issues/IssueController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueController
 *
 * @ingroup api_v1_issues
 *
 * @brief Controller class to handle API requests for issues operations.
 *
 */

namespace APP\API\v1\issues;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\security\authorization\OjsIssueRequiredPolicy;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;
use PKP\submission\GenreDAO;
use PKP\userGroup\UserGroup;

class IssueController extends PKPBaseController
{
    /** @var int The default number of issues to return in one request */
    public const DEFAULT_COUNT = 20;

    /** @var int The maximum number of issues to return in one request */
    public const MAX_COUNT = 100;

    /**
     * @copydoc \PKP\core\PKPBaseController::getHandlerPath()
     */
    public function getHandlerPath(): string
    {
        return 'issues';
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::getRouteGroupMiddleware()
     */
    public function getRouteGroupMiddleware(): array
    {
        return [
            'has.user',
            'has.context',
            self::roleAuthorizer([
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
                Role::ROLE_ID_REVIEWER,
                Role::ROLE_ID_AUTHOR,
            ]),
        ];
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        Route::get('', $this->getMany(...))
            ->name('issue.getMany');

        Route::get('current', $this->getCurrent(...))
            ->name('issue.current');

        Route::get('{issueId}', $this->get(...))
            ->name('issue.getIssue')
            ->whereNumber('issueId');
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::authorize()
     */
    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $this->addPolicy(new UserRolesRequiredPolicy($request), true);
        $this->addPolicy(new ContextRequiredPolicy($request));
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        $this->addPolicy(new OjsJournalMustPublishPolicy($request));

        $illuminateRequest = $args[0]; /** @var \Illuminate\Http\Request $illuminateRequest */

        if (static::getRouteActionName($illuminateRequest) === 'get') {
            $this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Get a collection of issues
     *
     * @hook API::issues::params [[&$collector, $illuminateRequest]]
     */
    public function getMany(Request $illuminateRequest): JsonResponse
    {
        $collector = Repo::issue()->getCollector()
            ->limit(self::DEFAULT_COUNT)
            ->offset(0);

        $request = $this->getRequest();
        $currentUser = $request->getUser();
        $context = $request->getContext();

        if (!$context) {
            return response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Process query params to format incoming data as needed
        foreach ($illuminateRequest->query() as $param => $val) {
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
                    $values = array_map(intval(...), $val);
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
                    $collector->filterByDoiStatuses(array_map(intval(...), paramToArray($val)));
                    break;
                case 'hasDois':
                    $collector->filterByHasDois((bool) $val, $context->getEnabledDoiTypes());
            }
        }

        $collector->filterByContextIds([$context->getId()]);

        Hook::call('API::issues::params', [&$collector, $illuminateRequest]);

        // You must be a manager or site admin to access unpublished Issues
        $isAdmin = $currentUser->hasRole([Role::ROLE_ID_MANAGER], $context->getId()) || $currentUser->hasRole([Role::ROLE_ID_SITE_ADMIN], \PKP\core\PKPApplication::SITE_CONTEXT_ID);
        if (isset($collector->isPublished) && !$collector->isPublished && !$isAdmin) {
            return response()->json([
                'error' => __('api.submissions.403.unpublishedIssues'),
            ], Response::HTTP_FORBIDDEN);
        } elseif (!$isAdmin) {
            $collector->filterByPublished(true);
        }

        $issues = $collector->getMany();

        return response()->json([
            'items' => Repo::issue()->getSchemaMap()->summarizeMany($issues, $context)->values(),
            'itemsMax' => $collector->getCount(),
        ], Response::HTTP_OK);
    }

    /**
     * Get the current issue
     */
    public function getCurrent(Request $illuminateRequest): JsonResponse
    {
        $context = $this->getRequest()->getContext();

        $issue = Repo::issue()->getCurrent($context->getId());

        if (!$issue) {
            return response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        $data = Repo::issue()->getSchemaMap()->map(
            $issue,
            $context,
            $this->getUserGroups($context->getId()),
            $this->getGenres($context->getId())
        );

        return response()->json($data, Response::HTTP_OK);
    }

    /**
     * Get a single issue
     */
    public function get(Request $illuminateRequest): JsonResponse
    {
        $context = $this->getRequest()->getContext();

        $issue = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_ISSUE);

        if (!$issue) {
            return response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        $data = Repo::issue()->getSchemaMap()->map(
            $issue,
            $context,
            $this->getUserGroups($context->getId()),
            $this->getGenres($context->getId())
        );

        return response()->json($data, Response::HTTP_OK);
    }

    protected function getUserGroups(int $contextId): Collection
    {
        return UserGroup::withContextIds([$contextId])->get();
    }

    protected function getGenres(int $contextId): array
    {
        $genreDao = DAORegistry::getDAO('GenreDAO'); /** @var GenreDAO $genreDao */
        return $genreDao->getByContextId($contextId)->toArray();
    }
}
