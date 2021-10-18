<?php

/**
 * @file classes/facade/Repo.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repo
 *
 * @brief Extends the base Repo facade with any overrides for OJS
 */

namespace APP\facades;

use APP\issue\Repository as IssueRepository;
use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;
use APP\submissionFile\Repository as SubmissionFileRepository;
use APP\user\Repository as UserRepository;

use Illuminate\Support\Facades\App;

class Repo extends \PKP\facades\Repo
{
    public static function issue(): IssueRepository
    {
        return App::make(IssueRepository::class);
    }

    public static function publication(): PublicationRepository
    {
        return App::make(PublicationRepository::class);
    }

    public static function submission(): SubmissionRepository
    {
        return App::make(SubmissionRepository::class);
    }

    public static function user(): UserRepository
    {
        return App::make(UserRepository::class);
    }

    public static function submissionFiles(): SubmissionFileRepository
    {
        return app()->make(SubmissionFileRepository::class);
    }
}
