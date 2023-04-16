<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I5716_EmailTemplateAssignments.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I5716_EmailTemplateAssignments
 *
 * @brief Refactors relationship between Mailables and Email Templates
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Collection;
use PKP\mail\mailables\DiscussionCopyediting;
use PKP\mail\mailables\DiscussionProduction;
use PKP\mail\mailables\DiscussionReview;
use PKP\mail\mailables\DiscussionSubmission;

class I5716_EmailTemplateAssignments extends \PKP\migration\upgrade\v3_4_0\I5716_EmailTemplateAssignments
{
    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }

    protected function getContextIdColumn(): string
    {
        return 'journal_id';
    }

    protected function getDiscussionTemplates(): Collection
    {
        return collect([
            DiscussionSubmission::getEmailTemplateKey(),
            DiscussionReview::getEmailTemplateKey(),
            DiscussionCopyediting::getEmailTemplateKey(),
            DiscussionProduction::getEmailTemplateKey(),
        ]);
    }
}
