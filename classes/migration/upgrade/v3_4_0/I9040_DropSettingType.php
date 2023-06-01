<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I9040_DropSettingType.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9040_DropSettingType
 *
 * @brief Drop not needed setting_type fields
 */

namespace APP\migration\upgrade\v3_4_0;

class I9040_DropSettingType extends \PKP\migration\upgrade\v3_4_0\I9040_DropSettingType
{
    /**
     * @copydoc \PKP\migration\upgrade\v3_4_0\I9040_DropSettingType::getEntities()
     */
    protected function getEntities(): array
    {
        return array_merge(parent::getEntities(), ['journal_settings', 'issue_settings']);
    }
}
