<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7264_UpdateEmailTemplates.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7264_UpdateEmailTemplates
 *
 * @brief Describe upgrade/downgrade operations for DB table email_templates.
 */

namespace APP\migration\upgrade\v3_4_0;

class I7264_UpdateEmailTemplates extends \PKP\migration\upgrade\v3_4_0\I7264_UpdateEmailTemplates
{
    protected function oldNewVariablesMap(): array
    {
        $oldNewVariablesMap = parent::oldNewVariablesMap();
        array_walk_recursive($oldNewVariablesMap, function (&$newVariable, $oldVariable) {
            if ($newVariable === 'contextName') {
                $newVariable = 'journalName';
            } elseif ($newVariable === 'contextUrl') {
                $newVariable = 'journalUrl';
            } elseif ($newVariable === 'contextSignature') {
                $newVariable = 'journalSignature';
            } elseif ($newVariable === 'contextAcronym') {
                $newVariable = 'journalAcronym';
            }
        });

        return $oldNewVariablesMap;
    }
}
