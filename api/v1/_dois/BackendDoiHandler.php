<?php

/**
 * @file api/v1/_dois/BackendDoiHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BackendDoiHandler
 *
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

namespace APP\API\v1\_dois;

use APP\facades\Repo;
use Illuminate\Support\LazyCollection;
use PKP\core\APIResponse;
use PKP\db\DAORegistry;
use PKP\security\Role;
use Slim\Http\Request as SlimRequest;

class BackendDoiHandler extends \PKP\API\v1\_dois\PKPBackendDoiHandler
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
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . "/galleys/{galleyId:\d+}",
                    'handler' => [$this, 'editGalley'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ]
            ]
        ]);
        parent::__construct();
    }

    /**
     * Edit galley to add DOI
     *
     * @throws \Exception
     */
    public function editGalley(SlimRequest $slimRequest, APIResponse $response, array $args): \Slim\Http\Response
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        $galley = Repo::galley()->get((int)$args['galleyId']);
        if (!$galley) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $publicationId = $galley->getData('publicationId');
        $publication = Repo::publication()->get((int)$publicationId);
        $submissionId = $publication->getData('submissionId');
        $submission = Repo::submission()->get((int) $submissionId);

        if ($submission->getData('contextId') !== $context->getId()) {
            return $response->withStatus(403)->withJsonError('api.dois.403.editItemOutOfContext');
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_GALLEY, $slimRequest->getParsedBody());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        Repo::galley()->edit($galley, ['doiId' => $doi->getId()]);

        /** @var \PKP\submission\GenreDAO $genreDao */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getByContextId($context->getId())->toArray();
        // Re-fetch submission and publication to reflect changes in galley
        $submission = Repo::submission()->get((int) $submissionId);
        $publication = Repo::publication()->get((int) $publicationId);
        $galley = Repo::galley()->get($galley->getId());

        $galleyProps = Repo::galley()->getSchemaMap($submission, $publication, $genres)->map($galley);

        return $response->withJson($galleyProps, 200);
    }

    /**
     * Edit issue to add DOI
     *
     * @throws \Exception
     */
    public function editIssue(SlimRequest $slimRequest, APIResponse $response, array $args): \Slim\Http\Response
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        $issue = Repo::issue()->get($args['issueId']);
        if (!$issue) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        if ($issue->getData('journalId') !== $context->getId()) {
            return $response->withStatus(403)->withJsonError('api.dois.403.editItemOutOfContext');
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_ISSUE, $slimRequest->getParsedBody());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        Repo::issue()->edit($issue, ['doiId' => $doi->getId()]);
        $issue = Repo::issue()->get($issue->getId());

        return $response->withJson(Repo::issue()->getSchemaMap()->map(
            $issue,
            $context,
            $this->getUserGroups($context->getId()),
            $this->getGenres($context->getId())
        ), 200);
    }

    protected function getUserGroups(int $contextId): LazyCollection
    {
        return Repo::userGroup()->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany();
    }

    protected function getGenres(int $contextId): array
    {
        /** @var \PKP\submission\GenreDAO $genreDao */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        return $genreDao->getByContextId($contextId)->toArray();
    }
}
