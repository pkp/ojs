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
 * @brief Upgrade migration add recommendations
 *
 */

namespace APP\migration\upgrade\v3_6_0;

use APP\facades\Repo;

class I1660_ReviewerRecommendations extends \PKP\migration\upgrade\v3_6_0\I1660_ReviewerRecommendations
{
    /**
     * @copydoc \PKP\migration\upgrade\v3_6_0\I1660_ReviewerRecommendations::systemDefineNonRemovableRecommendations()
     */
    protected function systemDefineNonRemovableRecommendations(): array
    {
        return Repo::reviewerRecommendation()->getDefaultRecommendations();
    }
}
