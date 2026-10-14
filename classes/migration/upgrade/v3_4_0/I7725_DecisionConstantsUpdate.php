<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7725_DecisionConstantsUpdate.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7725_DecisionConstantsUpdate
 *
 * @brief Editorial decision constant sync up across all application
 *
 * @see https://github.com/pkp/pkp-lib/issues/7725
 */

namespace APP\migration\upgrade\v3_4_0;

class I7725_DecisionConstantsUpdate extends \PKP\migration\upgrade\v3_4_0\I7725_DecisionConstantsUpdate
{
    /**
     * Get the decisions constants mappings
     *
     */
    public function getDecisionMappings(): array
    {
        // stage_id filtering removed: all old OJS 3.3 decision values are unique,
        // and the parent class's updated_at tracking mechanism prevents collisions
        // between sequential mappings (e.g., 1→2 then 2→4).
        // OJS 3.3 had no validation on which decisions could be recorded at which
        // stages, so decisions can exist at any stage in legacy data.
        // See https://github.com/pkp/pkp-lib/issues/12357
        return [
            ['current_value' => 1,  'updated_value' => 2],   // ACCEPT
            ['current_value' => 8,  'updated_value' => 3],   // EXTERNAL_REVIEW
            ['current_value' => 2,  'updated_value' => 4],   // PENDING_REVISIONS
            ['current_value' => 3,  'updated_value' => 5],   // RESUBMIT
            ['current_value' => 4,  'updated_value' => 6],   // DECLINE
            ['current_value' => 9,  'updated_value' => 8],   // INITIAL_DECLINE
            ['current_value' => 11, 'updated_value' => 9],   // RECOMMEND_ACCEPT
            ['current_value' => 12, 'updated_value' => 10],  // RECOMMEND_PENDING_REVISIONS
            ['current_value' => 13, 'updated_value' => 11],  // RECOMMEND_RESUBMIT
            ['current_value' => 14, 'updated_value' => 12],  // RECOMMEND_DECLINE
            ['current_value' => 16, 'updated_value' => 14],  // NEW_EXTERNAL_ROUND
            ['current_value' => 17, 'updated_value' => 15],  // REVERT_DECLINE
            ['current_value' => 18, 'updated_value' => 16],  // REVERT_INITIAL_DECLINE
            ['current_value' => 19, 'updated_value' => 17],  // SKIP_EXTERNAL_REVIEW
            ['current_value' => 31, 'updated_value' => 29],  // BACK_FROM_PRODUCTION
            ['current_value' => 32, 'updated_value' => 30],  // BACK_FROM_COPYEDITING
            ['current_value' => 33, 'updated_value' => 31],  // CANCEL_REVIEW_ROUND
        ];
    }
}
