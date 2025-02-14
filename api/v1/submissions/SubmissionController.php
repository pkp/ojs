<?php

/**
 * @file api/v1/submissions/SubmissionController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionController
 *
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

namespace APP\API\v1\submissions;

use APP\components\forms\publication\IssueEntryForm;
use APP\components\forms\publication\SubmissionPaymentsForm;
use APP\core\Application;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\publication\Publication;
use APP\submission\Collector;
use APP\submission\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\components\forms\publication\TitleAbstractForm;
use PKP\context\Context;
use PKP\security\Role;

class SubmissionController extends \PKP\API\v1\submissions\PKPSubmissionController
{
    public function __construct()
    {
        array_push($this->requiresSubmissionAccess, 'getPublicationIssueForm', 'getSubmissionPaymentForm');
    }

    /** @copydoc PKPSubmissionHandler::getSubmissionCollector() */
    protected function getSubmissionCollector(array $queryParams): Collector
    {
        $collector = parent::getSubmissionCollector($queryParams);

        if (isset($queryParams['issueIds'])) {
            $collector->filterByIssueIds(
                array_map(intval(...), paramToArray($queryParams['issueIds']))
            );
        }

        if (isset($queryParams['sectionIds'])) {
            $collector->filterBySectionIds(
                array_map(intval(...), paramToArray($queryParams['sectionIds']))
            );
        }

        return $collector;
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        parent::getGroupRoutes();

        Route::middleware([
            self::roleAuthorizer([Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_ASSISTANT]),
        ])->group(function () {
            Route::prefix('{submissionId}/publications/{publicationId}/_components')->group(function () {
                Route::get('issue', $this->getPublicationIssueForm(...))->name('submission.publication._components.issue');
                Route::get('submissionPayment', $this->getSubmissionPaymentForm(...))->name('submission.publication._components.submissionPayment');

            })->whereNumber(['submissionId', 'publicationId']);
        });

    }

    /**
     * Get IssueEntryForm form component
    */
    protected function getPublicationIssueForm(Request $illuminateRequest): JsonResponse
    {
        $data = $this->getSubmissionAndPublicationData($illuminateRequest);

        if (isset($data['error'])) {
            return response()->json([ 'error' => $data['error'],], $data['status']);
        }

        $request = $this->getRequest();
        $submission = $data['submission']; /** @var Submission $submission */
        $publication = $data['publication']; /** @var Publication $publication*/
        $context = $data['context']; /** @var Context $context*/
        $publicationApiUrl = $data['publicationApiUrl']; /** @var String $publicationApiUrl*/
        $locales = $this->getPublicationFormLocales($context, $submission);
        $temporaryFileApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $context->getPath(), 'temporaryFiles');

        $publicFileManager = new PublicFileManager();
        $baseUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId());

        // This form provides Issue details for a submission's publication.
        // This includes fields to change the Issue and section that the submission the publication is linked to, cover image, page and publication date details.
        $issueEntryForm = new IssueEntryForm(
            $publicationApiUrl,
            $locales,
            $publication,
            $context,
            $baseUrl,
            $temporaryFileApiUrl
        );

        return response()->json($this->getLocalizedForm($issueEntryForm, $submission->getData('locale'), $locales), Response::HTTP_OK);
    }

    /**
     * Get SubmissionPaymentsForm
    */
    protected function getSubmissionPaymentForm(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $data = $this->getSubmissionAndPublicationData($illuminateRequest);

        if (isset($data['error'])) {
            return response()->json([ 'error' => $data['error'],], $data['status']);
        }

        $submission = $data['submission']; /** @var Submission $submission */
        $context = $data['context']; /** @var Context $context*/
        $paymentManager = Application::get()->getPaymentManager($context);

        if (!$paymentManager->publicationEnabled()) {
            return response()->json([
                'error' => __('api.publications.403.paymentFeesNotEnabled'),
            ], Response::HTTP_FORBIDDEN);
        }

        $submissionPaymentsForm = new SubmissionPaymentsForm(
            $request->getDispatcher()->url($request, Application::ROUTE_API, $context->getPath(), '_submissions/' . $submission->getId() . '/payment'),
            $submission,
            $context
        );

        return response()->json($submissionPaymentsForm->getConfig(), Response::HTTP_OK);
    }

    /**
     * @copydoc \PKP\api\v1\submissions\PKPSubmissionController::getPublicationTitleAbstractForm()
     */
    protected function getPublicationTitleAbstractForm(Request $illuminateRequest): JsonResponse
    {
        $data = $this->getSubmissionAndPublicationData($illuminateRequest);

        if (isset($data['error'])) {
            return response()->json([ 'error' => $data['error'],], $data['status']);
        }

        $submission = $data['submission']; /** @var Submission $submission */
        $publication = $data['publication']; /** @var Publication $publication*/
        $context = $data['context']; /** @var Context $context*/
        $publicationApiUrl = $data['publicationApiUrl']; /** @var String $publicationApiUrl*/

        $section = Repo::section()->get($publication->getData('sectionId'), $context->getId());
        $locales = $this->getPublicationFormLocales($context, $submission);
        $submissionLocale = $submission->getData('locale');

        $titleAbstract = new TitleAbstractForm(
            $publicationApiUrl,
            $locales,
            $publication,
            (int) $section->getData('wordCount'),
            !$section->getData('abstractsNotRequired')
        );

        return response()->json($this->getLocalizedForm($titleAbstract, $submissionLocale, $locales), Response::HTTP_OK);
    }
}
