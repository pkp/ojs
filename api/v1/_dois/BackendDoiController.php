<?php

/**
 * @file api/v1/_dois/BackendDoiController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BackendDoiController
 *
 * @ingroup api_v1_backend
 *
 * @brief Controller class to handle API requests for backend operations.
 *
 */

namespace APP\API\v1\_dois;

use APP\facades\Repo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\db\DAORegistry;
use PKP\userGroup\UserGroup;

class BackendDoiController extends \PKP\API\v1\_dois\PKPBackendDoiController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        parent::getGroupRoutes();

        Route::put('issues/{issueId}', $this->editIssue(...))
            ->name('_doi.backend.issue.edit')
            ->whereNumber('issueId');

        Route::put('galleys/{galleyId}', $this->editGalley(...))
            ->name('_doi.backend.galley.edit')
            ->whereNumber('galleyId');
    }

    /**
     * Edit galley to add DOI
     *
     * @throws \Exception
     */
    public function editGalley(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        $galley = Repo::galley()->get((int)$illuminateRequest->route('galleyId'));
        if (!$galley) {
            return response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        $publicationId = $galley->getData('publicationId');
        $publication = Repo::publication()->get((int)$publicationId);
        $submissionId = $publication->getData('submissionId');
        $submission = Repo::submission()->get((int) $submissionId);

        if ($submission->getData('contextId') !== $context->getId()) {
            return response()->json([
                'error' => __('api.dois.403.editItemOutOfContext'),
            ], Response::HTTP_FORBIDDEN);
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_GALLEY, $illuminateRequest->input());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return response()->json([
                'error' => __('api.dois.404.doiNotFound'),
            ], Response::HTTP_NOT_FOUND);
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

        return response()->json($galleyProps, Response::HTTP_OK);
    }

    /**
     * Edit issue to add DOI
     *
     * @throws \Exception
     */
    public function editIssue(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        $issue = Repo::issue()->get($illuminateRequest->route('issueId'));
        if (!$issue) {
            return response()->json([
                'error' => __('api.404.resourceNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        if ($issue->getData('journalId') !== $context->getId()) {
            return response()->json([
                'error' => __('api.dois.403.editItemOutOfContext'),
            ], Response::HTTP_FORBIDDEN);
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_ISSUE, $illuminateRequest->input());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return response()->json([
                'error' => __('api.dois.404.doiNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        Repo::issue()->edit($issue, ['doiId' => $doi->getId()]);
        $issue = Repo::issue()->get($issue->getId());
        $userGroups = UserGroup::withContextIds([$context->getId()])->get();

        return response()->json(
            Repo::issue()
                ->getSchemaMap()
                ->map(
                    $issue,
                    $context,
                    $userGroups,
                    $this->getGenres($context->getId())
                ),
            Response::HTTP_OK
        );
    }

    protected function getGenres(int $contextId): array
    {
        /** @var \PKP\submission\GenreDAO $genreDao */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        return $genreDao->getByContextId($contextId)->toArray();
    }
}
