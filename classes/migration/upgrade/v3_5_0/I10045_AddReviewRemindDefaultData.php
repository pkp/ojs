<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I10045_AddReviewRemindDefaultData.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10045_AddReviewRemindDefaultData
 *
 * @brief Seed the REVIEW_REMIND email template into email_templates_default_data if it is missing.
 */

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Support\Facades\DB;
use PKP\migration\upgrade\v3_5_0\InstallEmailTemplates;

class I10045_AddReviewRemindDefaultData extends InstallEmailTemplates
{
    protected function getEmailTemplateKeys(): array
    {
        return [
            'REVIEW_REMIND',
        ];
    }

    public function down(): void
    {
        DB::table('email_templates_default_data')
            ->where('email_key', 'REVIEW_REMIND')
            ->delete();

        DB::table('email_templates')
            ->where('email_key', 'REVIEW_REMIND')
            ->delete();
    }
}
