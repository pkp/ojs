<?php

/**
 * @file classes/migration/install/ReviewerRecommendationsMigration.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerRecommendationsMigration
 *
 * @brief
 */

namespace APP\migration\install;

class ReviewerRecommendationsMigration extends \PKP\migration\install\ReviewerRecommendationsMigration
{
    public function contextTable(): string
    {
        return 'journals';
    }

    public function contextPrimaryKey(): string
    {
        return 'journal_id';
    }
}
