<?php

/**
 * @file plugins/pubIds/urn/classes/form/FieldTextUrn.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldTextUrn
 *
 * @brief A field for entering a custom URN that also considers adding a check number.
 */

namespace APP\plugins\pubIds\urn\classes\form;

use PKP\components\forms\FieldText;

class FieldTextUrn extends FieldText
{
    /** @copydoc Field::$component */
    public $component = 'field-text-urn';

    public string $urnPrefix = '';

    public bool $applyCheckNumber = false;

    /**
     * @copydoc Field::getConfig()
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config['urnPrefix'] = $this->urnPrefix;
        $config['applyCheckNumber'] = $this->applyCheckNumber;
        $config['addCheckNumberLabel'] = __('plugins.pubIds.urn.editor.addCheckNo');
        return $config;
    }
}
