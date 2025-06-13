<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I10962_UpdateEmailTemplateVariables.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10962_UpdateEmailTemplateVariables
 *
 * @brief Remap {$contextAcronym} to {$journalAcronym} in the COPYEDIT_REQUEST email template.
 */

namespace APP\migration\upgrade\v3_4_0;

use PKP\migration\upgrade\v3_4_0\I7264_UpdateEmailTemplates as BaseMigration;

class I10962_UpdateEmailTemplateVariables extends BaseMigration
{
    public function up(): void
    {
        $map = $this->oldNewVariablesMap();
        $this->renameTemplateVariables($map);
    }

    protected function oldNewVariablesMap(): array
    {
        return [
            'COPYEDIT_REQUEST' => ['contextAcronym' => 'journalAcronym'],
        ];
    }
}
