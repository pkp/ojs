<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I1660_ReviewerRecommendations.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I1660_ReviewerRecommendations.php
 *
 * @brief
 *
 */

namespace APP\migration\upgrade\v3_6_0;

use APP\migration\install\ReviewerRecommendationsMigration;

class I1660_ReviewerRecommendations extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        (new ReviewerRecommendationsMigration($this->_installer, $this->_attributes))->up();
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        (new ReviewerRecommendationsMigration($this->_installer, $this->_attributes))->down();
    }
}
