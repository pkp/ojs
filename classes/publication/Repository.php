<?php

/**
 * @file classes/publication/Repository.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief Get publications and information about publications
 */

namespace APP\publication;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\enums\IssueSelection;
use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\payment\ojs\OJSPaymentManager;
use APP\publication\enums\VersionStage;
use APP\submission\Submission;
use Illuminate\Support\Facades\App;
use PKP\context\Context;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\db\DAORegistry;

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
                $allowedLocales = $submission->getPublicationLanguages($context->getSupportedSubmissionMetadataLocales());
                foreach ($allowedLocales as $localeKey) {
                    if (empty($props['abstract'][$localeKey])) {
                        continue;
                    }
                    $wordCount = PKPString::getWordCount($props['abstract'][$localeKey]);
                    $wordCountLimit = $section->getData('wordCount');
                    if ($wordCountLimit && $wordCount > $wordCountLimit) {
                        if (!isset($errors['abstract'])) {
                            $errors['abstract'] = [];
                        };
                        $errors['abstract'][$localeKey] = [__('publication.wordCountLong', ['limit' => $wordCountLimit, 'count' => $wordCount])];
                    }
                }
            }
        }

        // Ensure that the valid issue exists is any issue selected
        if (isset($props['issueId']) && empty($errors['issueId'])) {

            // Will allow to create a publication without an issue
            if ($props['issueId'] == IssueSelection::NO_ISSUE->value) {
                return $errors;
            }

            if (in_array($props['issueId'], IssueSelection::unselectableOptionsValue())
                || !Repo::issue()->exists($props['issueId'])) {
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
        $publication->setData('published', false);
        $newId = parent::version($publication, $versionStage, $isMinorVersion);

        $context = Application::get()->getRequest()->getContext();

        $galleys = $publication->getData('galleys');
        $isDoiVersioningEnabled = $context->getData(Context::SETTING_DOI_VERSIONING);
        if (!empty($galleys)) {
            foreach ($galleys as $galley) {
                $newGalley = clone $galley;
                $newGalley->setData('id', null);
                $newGalley->setData('publicationId', $newId);
                if ($isDoiVersioningEnabled) {
                    $newGalley->setData('doiId', null);
                }
                Repo::galley()->add($newGalley);
            }
        }

        return $newId;
    }

    /**
     * @copydoc \PKP\publication\Repository::publish()
     */
    public function publish(Publication $publication)
    {
        // if there is no issue, set the publication as published as part of continuous publication
        if (!$publication->getData('issueId')) {
            $publication->setData('published', true);
        } else {
            $issue = Repo::issue()->get($publication->getData('issueId'));
            if ($issue->getData('published')) {
                $publication->setData('published', true);
            }
        }

        parent::publish($publication);
    }

    /**
     * @copydoc \PKP\publication\Repository::unpublish()
     */
    public function unpublish(Publication $publication)
    {
        $publication->setData('published', false);
        parent::unpublish($publication);
    }

    /**
     * @copydoc \PKP\publication\Repository::edit()
     */
    public function edit(Publication $publication, array $params): Publication
    {
        if (array_key_exists('issueId', $params) && $params['issueId'] == IssueSelection::NO_ISSUE->value) {
            unset($params['issueId']);
        }

        if (isset($params['issueId'])) {
            $issue = Repo::issue()->get($params['issueId']);

            // Attached to a future issue e.g. non published issue
            // and marked as continuous publication
            if (!$issue->getData('published')) {
                $params['published'] = filter_var(
                    $params['continuousPublication'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        return parent::edit($publication, $params);
    }

    /**
     * Set the publication status and datePublished on publish
     *
     * When an issue is published, any attached publications inherit the
     * issue's `datePublished` in IssueGridHandler if they do not already
     * have a specific publication date set.
     *
     * - If the issue is **not published**, the publication status is set to `STATUS_SCHEDULED`.
     * - If the issue is **published** or there is no issue, the status is set to `STATUS_PUBLISHED`.
     * - If the publication is a **continuous publication**, the status is set to `STATUS_PUBLISHED`
     *   regardless of the issue's publication status.
     * - If the publication does not have a `datePublished`, it is set to the current date.
     */
    protected function setStatusOnPublish(Publication $publication)
    {
        if ($publication->getData('issueId') && ($issue = Repo::issue()->get($publication->getData('issueId')))) {
            // If there is an issue
            //   - set the publication status to STATUS_PUBLISHED if issue is published
            //   - set the publication status to STATUS_SCHEDULED if issue is not published
            $publication->setData(
                'status',
                $issue->getData('published')
                    ? Submission::STATUS_PUBLISHED
                    : Submission::STATUS_SCHEDULED
            );
        } else {
            // if there is no issue, set the publication status to STATUS_PUBLISHED
            // as it is a issueless publication
            $publication->setData('status', Submission::STATUS_PUBLISHED);
        }

        // if the publication is marked as part of continuous publication, set the status to STATUS_PUBLISHED
        // regardless of the issue's publication status is any issue is associated with the publication
        if ($publication->isMarkedAsContinuousPublication()) {
            $publication->setData('status', Submission::STATUS_PUBLISHED);
        }

        // If no predefined datePublished available for the publication
        // or if the publication is marked as part of continuous publication
        // use current date to set/update the date published
        if ($publication->getData('status') === Submission::STATUS_PUBLISHED && !$publication->getData('datePublished')
            // && (!$publication->getData('datePublished') || $publication->isMarkedAsContinuousPublication())
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

    /**
     * Create all DOIs associated with the publication.
     *
     * @throws \Exception
     */
    protected function createDois(Publication $newPublication): void
    {
        $submission = Repo::submission()->get($newPublication->getData('submissionId'));
        Repo::submission()->createDois($submission);
    }
}
