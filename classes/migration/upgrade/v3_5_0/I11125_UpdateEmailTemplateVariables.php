<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I11125_UpdateEmailTemplateVariables.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I11125_UpdateEmailTemplateVariables
 *
 * @brief Migration to update Email Template variable names
 */

namespace APP\migration\upgrade\v3_5_0;

class I11125_UpdateEmailTemplateVariables extends \PKP\migration\upgrade\v3_5_0\I11125_UpdateEmailTemplateVariables
{
    public function oldToNewVariablesMap(): array
    {
        return [
            'COPYEDIT_REQUEST' => [
                'journalAcronym' => 'contextAcronym',
            ],
            'LAYOUT_REQUEST' => [
                'journalAcronym' => 'contextAcronym',
            ],
        ];
    }
}
