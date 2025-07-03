<?php

/**
 * @file classes/publication/Repository.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief Get publications and information about publications
 */

namespace APP\publication;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\enums\IssueAssignment;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\payment\ojs\OJSPaymentManager;
use APP\publication\enums\VersionStage;
use APP\submission\Submission;
use Illuminate\Support\Facades\App;
use PKP\context\Context;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\doi\exceptions\DoiException;

class Repository extends \PKP\publication\Repository
{
    /** @copydoc \PKP\submission\Repository::$schemaMap */
    public $schemaMap = maps\Schema::class;

    public function getCollector(): Collector
    {
        return App::makeWith(Collector::class, ['dao' => $this->dao]);
    }

    /** @copydoc PKP\publication\Repository::validate() */
    public function validate($publication, array $props, Submission $submission, Context $context): array
    {
        $errors = parent::validate($publication, $props, $submission, $context);
        $submissionLocale = $submission->getData('locale');

        // Ensure that the specified section exists
        $section = null;
        if (isset($props['sectionId'])) {
            $section = Repo::section()->get($props['sectionId'], $context->getId());
            if (!$section) {
                $errors['sectionId'] = [__('publication.invalidSection')];
            }
        }

        // Get the section so we can validate section abstract requirements
        if (!$section && !is_null($publication)) {
            $section = Repo::section()->get($publication->getData('sectionId'), $context->getId());
        }

        // Only validate section settings for completed submissions
        if ($section && !$submission->getData('submissionProgress')) {
            // Require abstracts for new publications if the section requires them
            if (is_null($publication) && !$section->getData('abstractsNotRequired') && empty($props['abstract'])) {
                $errors['abstract'][$submissionLocale] = [__('author.submit.form.abstractRequired')];
            }

            if (isset($props['abstract']) && empty($errors['abstract'])) {
                // Require abstracts in the primary language if the section requires them
                if (!$section->getData('abstractsNotRequired')) {
                    if (empty($props['abstract'][$submissionLocale])) {
                        if (!isset($errors['abstract'])) {
                            $errors['abstract'] = [];
                        };
                        $errors['abstract'][$submissionLocale] = [__('author.submit.form.abstractRequired')];
                    }
                }

                // Check the word count on abstracts
                $wordCountLimit = $section->getData('wordCount');
                if ($wordCountLimit) {
                    $abstractErrors = $this->validateWordCount(
                        $context,
                        $submission,
                        $wordCountLimit,
                        'publication.abstract.wordCountLong',
                        $props['abstract']
                    );
                    if (count($abstractErrors)) {
                        $errors['abstract'] = $abstractErrors;
                    }
                }
            }
        }

        if (isset($props['plainLanguageSummary'])) {
            // Check the word count on plain language summary
            $wordCountLimit = $section->getData('wordCount');
            if ($wordCountLimit) {
                $plainLanguageSummaryErrors = $this->validateWordCount(
                    $context,
                    $submission,
                    $wordCountLimit,
                    'publication.plainLanguageSummary.wordCountLong',
                    $props['plainLanguageSummary'],
                );
                if (count($plainLanguageSummaryErrors)) {
                    $errors['plainLanguageSummary'] = $plainLanguageSummaryErrors;
                }
            }
        }

        // Ensure that the valid issue exists is any issue selected
        if (isset($props['issueId']) && empty($errors['issueId'])) {
            if (!Repo::issue()->exists($props['issueId'])) {
                $errors['issueId'] = [__('publication.invalidIssue')];
            }
        }

        return $errors;
    }

    /** @copydoc PKP\publication\Repository::validatePublish() */
    public function validatePublish(Publication $publication, Submission $submission, array $allowedLocales, string $primaryLocale): array
    {
        $context = Application::get()->getRequest()->getContext();

        $errors = parent::validatePublish($publication, $submission, $allowedLocales, $primaryLocale);

        if ($publication->getData('issueId') && !Repo::issue()->get($publication->getData('issueId'))) {
            $errors['issueId'] = __('publication.invalidIssue');
        }

        // If submission fees are enabled, check that they're fulfilled
        if (!$context || $context->getId() !== $submission->getData('contextId')) {
            $context = app()->get('context')->get($submission->getData('contextId'));
        }
        $paymentManager = Application::get()->getPaymentManager($context);
        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
        $publicationFeeEnabled = $paymentManager->publicationEnabled();
        $publicationFeePayment = $completedPaymentDao->getByAssoc(null, OJSPaymentManager::PAYMENT_TYPE_PUBLICATION, $submission->getId());
        if ($publicationFeeEnabled && !$publicationFeePayment) {
            $errors['publicationFeeStatus'] = __('editor.article.payment.publicationFeeNotPaid');
        }

        return $errors;
    }

    /** @copydoc \PKP\publication\Repository::version() */
    public function version(Publication $publication, ?VersionStage $versionStage = null, bool $isMinorVersion = true): int
    {
        $newId = parent::version($publication, $versionStage, $isMinorVersion);

        $context = Application::get()->getRequest()->getContext();

        $galleys = $publication->getData('galleys');
        $isDoiVersioningEnabled = $context->getData(Context::SETTING_DOI_VERSIONING);
        if (!empty($galleys)) {
            foreach ($galleys as $galley) {
                $newGalley = clone $galley;
                $newGalley->setData('id', null);
                $newGalley->setData('publicationId', $newId);
                if ($isDoiVersioningEnabled && !$isMinorVersion) {
                    $newGalley->setData('doiId', null);
                }
                Repo::galley()->add($newGalley);
            }
        }

        return $newId;
    }

    /**
     * Set the publication status and datePublished on publish
     *
     * When an issue is published, any attached publications inherit the
     * issue's `datePublished` in IssueGridHandler if they do not already
     * have a specific publication date set.
     *
     * - If the issue is `STATUS_READY_TO_SCHEDULE`, the publication status is set to `STATUS_SCHEDULED`.
     * - If the issue is `STATUS_READY_TO_PUBLISH` or there is no issue, the status is set to `STATUS_PUBLISHED`.
     * - In the absense of of `STATUS_READY_TO_PUBLISH` or `STATUS_READY_TO_SCHEDULE`, then publication status
     *   in set based on the issue status if issue is set. if No issue, it's set to `STATUS_READY_TO_PUBLISH`
     * - If the publication does not have a `datePublished`, it is set to the current date when
     *   set to `STATUS_PUBLISHED` .
     */
    protected function setStatusOnPublish(Publication $publication)
    {
        $currentPublicaionStatus = (int) $publication->getData('status');

        if (in_array($currentPublicaionStatus, [Publication::STATUS_READY_TO_PUBLISH, Publication::STATUS_READY_TO_SCHEDULE])) {
            $publication->setData(
                'status',
                $currentPublicaionStatus === Publication::STATUS_READY_TO_PUBLISH
                    ? Publication::STATUS_PUBLISHED
                    : Publication::STATUS_SCHEDULED
            );
        } else {
            $issue = $publication->getData('issueId')
                ? Repo::issue()->get($publication->getData('issueId'))
                : null;

            if (!$issue) {
                $publication->setData('status', Publication::STATUS_PUBLISHED);
            } else {
                // If there is an issue
                //   - set the publication status to STATUS_PUBLISHED if issue is published
                //   - set the publication status to STATUS_SCHEDULED if issue is not published
                $publication->setData(
                    'status',
                    $issue->getData('published')
                        ? Publication::STATUS_PUBLISHED
                        : Publication::STATUS_SCHEDULED
                );
            }
        }

        // If no predefined datePublished available for the publication
        // and the publication is marked as published by above check
        // use current date to set/update the date published
        if ($publication->getData('status') == Publication::STATUS_PUBLISHED
            && !$publication->getData('datePublished')
        ) {
            $publication->setData('datePublished', Core::getCurrentDate());
        }
    }

    /** @copydoc \PKP\publication\Repository::delete() */
    public function delete(Publication $publication)
    {
        $galleys = Repo::galley()->getCollector()
            ->filterByPublicationIds([$publication->getId()])
            ->getMany();

        foreach ($galleys as $galley) {
            Repo::galley()->delete($galley);
        }

        parent::delete($publication);
    }

    /** @copydoc \PKP\publication\Repository::createDois() */
    public function createDois(Publication $publication): array
    {
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        /** @var JournalDAO $contextDao */
        $contextDao = Application::getContextDAO();
        /** @var Journal $context */
        $context = $contextDao->getById($submission->getData('contextId'));

        $doiCreationFailures = [];

        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_PUBLICATION) && empty($publication->getData('doiId'))) {
            try {
                $doiId = Repo::doi()->mintPublicationDoi($publication, $submission, $context);
                Repo::publication()->edit($publication, ['doiId' => $doiId]);
            } catch (DoiException $exception) {
                $doiCreationFailures[] = $exception;
            }
        }

        // Galleys
        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_REPRESENTATION)) {
            $galleys = $publication->getData('galleys');
            foreach ($galleys as $galley) {
                if (empty($galley->getData('doiId'))) {
                    try {
                        $doiId = Repo::doi()->mintGalleyDoi($galley, $publication, $submission, $context);
                        Repo::galley()->edit($galley, ['doiId' => $doiId]);
                    } catch (DoiException $exception) {
                        $doiCreationFailures[] = $exception;
                    }
                }
            }
        }

        return $doiCreationFailures;
    }

    /**
     * Get the possible issue assignment status for the publication
     */
    public function getIssueAssignmentStatus(Publication $publication, Context $context): IssueAssignment
    {
        $issue = $publication->getData('issueId')
            ? Repo::issue()->get($publication->getData('issueId'))
            : null;

        if ($publication->getData('status') == Publication::STATUS_QUEUED) {
            if (!$issue) {
                // As the is no issue association
                // if it was previously published and then got unpublished e.g has `date_published`
                // the it was previously issueless e.g. NO_ISSUE
                // otherwise we get the default assignment
                return $publication->getData('datePublished')
                    ? IssueAssignment::NO_ISSUE
                    : IssueAssignment::defaultAssignment($context);
            }

            // There is issue association and based on the assignment will be deduced
            return $issue->getData('published')
                ? IssueAssignment::CURRENT_BACK_ISSUES_PUBLISHED
                : (
                    // If the publication has a datePublished, it means it was published previously
                    // for future issue as continuous publication
                    // otherwise it was scheduled for publication
                    $publication->getData('datePublished')
                        ? IssueAssignment::FUTURE_ISSUES_PUBLISHED
                        : IssueAssignment::FUTURE_ISSUE_SCHEDULED
                );

        }

        if ($publication->getData('status') == Publication::STATUS_DECLINED) {
            return IssueAssignment::defaultAssignment($context);
        }

        if ($publication->getData('status') == Publication::STATUS_PUBLISHED
            || $publication->getData('status') == Publication::STATUS_READY_TO_PUBLISH) {

            if (!$issue) {
                return IssueAssignment::NO_ISSUE;
            }

            return $issue->getData('published')
                ? IssueAssignment::CURRENT_BACK_ISSUES_PUBLISHED
                : IssueAssignment::FUTURE_ISSUES_PUBLISHED;
        }

        if ($publication->getData('status') == Publication::STATUS_SCHEDULED
            || $publication->getData('status') == Publication::STATUS_READY_TO_SCHEDULE) {
            return IssueAssignment::FUTURE_ISSUE_SCHEDULED;
        }

        return IssueAssignment::defaultAssignment($context);
    }
}
