<?php

/**
 * @file classes/plugins/PubObjectsExportSettingsForm.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubObjectsExportSettingsForm
 *
 * @ingroup plugins
 *
 * @brief Abstract class that PubObjectsExportPlugin's plugins need to implement
 */

namespace APP\plugins;

use PKP\form\Form;

abstract class PubObjectsExportSettingsForm extends Form
{
    /**
     * Get form fields
     *
     * @return array (field name => field type)
     */
    abstract public function getFormFields(): array;

    /**
     * Is the form field optional
     */
    abstract public function isOptional(string $settingName): bool;
}
