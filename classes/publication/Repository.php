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
use APP\core\Services;
use APP\facades\Repo;
use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\payment\ojs\OJSPaymentManager;
use APP\submission\Submission;
use Illuminate\Support\Facades\App;
use PKP\context\Context;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\publication\Collector;

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

        $allowedLocales = $context->getSupportedSubmissionLocales();
        $primaryLocale = $submission->getLocale();

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
                $errors['abstract'][$primaryLocale] = [__('author.submit.form.abstractRequired')];
            }

            if (isset($props['abstract']) && empty($errors['abstract'])) {
                // Require abstracts in the primary language if the section requires them
                if (!$section->getData('abstractsNotRequired')) {
                    if (empty($props['abstract'][$primaryLocale])) {
                        if (!isset($errors['abstract'])) {
                            $errors['abstract'] = [];
                        };
                        $errors['abstract'][$primaryLocale] = [__('author.submit.form.abstractRequired')];
                    }
                }

                // Check the word count on abstracts
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

        // Ensure that the issueId exists
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
        $errors = parent::validatePublish($publication, $submission, $allowedLocales, $primaryLocale);

        // Every publication must be scheduled in an issue
        if (!$publication->getData('issueId') || !Repo::issue()->get($publication->getData('issueId'))) {
            $errors['issueId'] = __('publication.required.issue');
        }

        // If submission fees are enabled, check that they're fulfilled
        $context = Application::get()->getRequest()->getContext();
        if (!$context || $context->getId() !== $submission->getData('contextId')) {
            $context = Services::get('context')->get($submission->getData('contextId'));
        }
        $paymentManager = Application::getPaymentManager($context);
        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
        $publicationFeeEnabled = $paymentManager->publicationEnabled();
        $publicationFeePayment = $completedPaymentDao->getByAssoc(null, OJSPaymentManager::PAYMENT_TYPE_PUBLICATION, $submission->getId());
        if ($publicationFeeEnabled && !$publicationFeePayment) {
            $errors['publicationFeeStatus'] = __('editor.article.payment.publicationFeeNotPaid');
        }

        return $errors;
    }

    /** @copydoc \PKP\publication\Repository::version() */
    public function version(Publication $publication): int
    {
        $newId = parent::version($publication);

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

    /** @copydoc \PKP\publication\Repository::setStatusOnPublish() */
    protected function setStatusOnPublish(Publication $publication)
    {
        // A publication may be scheduled in a future issue. In such cases,
        // the `datePublished` should remain empty and the status should be set to
        // scheduled.
        //
        // If there is no assigned issue, the journal may be using a continuous
        // publishing model in which articles are published right away.
        $issue = Repo::issue()->get($publication->getData('issueId'));
        if ($issue && !$issue->getData('published')) {
            $publication->setData('datePublished', null);
            $publication->setData('status', Submission::STATUS_SCHEDULED);
        } else {
            $publication->setData('status', Submission::STATUS_PUBLISHED);
            if (!$publication->getData('datePublished')) {
                $publication->setData('datePublished', Core::getCurrentDate());
            }
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
