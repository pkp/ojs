<?php

/**
 * @file plugins/pubIds/urn/classes/form/FieldPubIdUrn.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldPubIdUrn
 *
 * @brief URN field, that is used for pattern suffixes and that considers check number.
 */

namespace APP\plugins\pubIds\urn\classes\form;

use PKP\components\forms\FieldPubId;

class FieldPubIdUrn extends FieldPubId
{
    /** @copydoc Field::$component */
    public $component = 'field-pub-id-urn';

    public bool $applyCheckNumber = false;

    /**
     * @copydoc Field::getConfig()
     */
    public function getConfig()
    {
        $config = parent::getConfig();
        $config['applyCheckNumber'] = $this->applyCheckNumber;
        return $config;
    }
}
