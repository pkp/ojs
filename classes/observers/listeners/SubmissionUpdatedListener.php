<?php

declare(strict_types=1);

/**
 * @file classes/observers/listeners/SubmissionUpdatedListener.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionUpdatedListener
 * @ingroup core
 *
 * @brief Listener fired when submission's updated
 */

namespace APP\observers\listeners;

use APP\article\ArticleTombstoneManager;
use APP\core\Services;
use APP\submission\Submission;
use Illuminate\Events\Dispatcher;
use PKP\db\DAORegistry;
use PKP\observers\events\PublishedEvent;
use PKP\observers\events\UnpublishedEvent;

class SubmissionUpdatedListener
{
    /**
     * Maps methods with correspondent events to listen
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            PublishedEvent::class,
            self::class . '@handlePublishedEvent'
        );

        $events->listen(
            UnpublishedEvent::class,
            self::class . '@handleUnpublished'
        );
    }

    public function handleUnpublished(UnpublishedEvent $event)
    {
        // If the submission is no longer considered published, create a tombstone.
        if ($event->submission->getData('status') !== Submission::STATUS_PUBLISHED) {
            $context = Services::get('context')->get($event->submission->getData('contextId'));
            $articleTombstoneManager = new ArticleTombstoneManager();
            $sectionDao = DAORegistry::getDAO('SectionDAO');
            $section = $sectionDao->getById($event->submission->getSectionId());
            $articleTombstoneManager->insertArticleTombstone($event->submission, $context, $section);
        }
    }

    public function handlePublishedEvent(PublishedEvent $event)
    {
        // Delete any existing article tombstone.
        $tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO'); /** @var DataObjectTombstoneDAO $tombstoneDao */
        $tombstoneDao->deleteByDataObjectId($event->submission->getId());
    }
}
