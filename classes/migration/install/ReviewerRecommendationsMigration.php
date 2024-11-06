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
 * @brief Describe database table structures .
 */

namespace APP\migration\install;

class ReviewerRecommendationsMigration extends \PKP\migration\install\ReviewerRecommendationsMigration
{
    /**
     * @copydoc \PKP\migration\install\ReviewerRecommendationsMigratio::contextTable()
     */
    public function contextTable(): string
    {
        return 'journals';
    }

    /**
     * @copydoc \PKP\migration\install\ReviewerRecommendationsMigratio::settingTable()
     */
    public function settingTable(): string
    {
        return 'journal_settings';
    }

    /**
     * @copydoc \PKP\migration\install\ReviewerRecommendationsMigratio::contextPrimaryKey()
     */
    public function contextPrimaryKey(): string
    {
        return 'journal_id';
    }
}
