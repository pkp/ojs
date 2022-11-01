<?php

declare(strict_types=1);

/**
 * @file classes/observers/listeners/SubmissionDeletedListener.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDeletedListener
 * @ingroup core
 *
 * @brief Listener fired when submission's deleted
 */

namespace APP\observers\listeners;

use APP\article\ArticleTombstoneManager;

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Events\Dispatcher;
use PKP\observers\events\SubmissionDeleted;

class SubmissionDeletedListener
{
    /**
     * Maps methods with correspondent events to listen
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            SubmissionDeleted::class,
            self::class . '@handle'
        );
    }

    /**
     * Handle the listener call
     */
    public function handle(SubmissionDeleted $event): void
    {
        $submission = Repo::submission()->get($event->submissionId);
        if (!$submission) {
            return;
        }

        $sectionDao = Application::get()->getSectionDao();
        $section = $sectionDao->getById($submission->getSectionId());
        if (!$section) {
            return;
        }

        $contextDao = Application::get()->getContextDao();
        $context = $contextDao->getById($submission->getContextId());
        if (!$context) {
            return;
        }

        $articleTombstoneManager = new ArticleTombstoneManager();
        $articleTombstoneManager->insertArticleTombstone($submission, $context, $section);
    }
}
