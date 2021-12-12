<?php

/**
 * @file plugins/metadata/dc11/schema/Dc11Schema.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Dc11Schema
 * @ingroup plugins_metadata_dc11_schema
 *
 * @see PKPDc11Schema
 *
 * @brief OJS-specific implementation of the Dc11Schema.
 */

import('lib.pkp.plugins.metadata.dc11.schema.PKPDc11Schema');

use PKP\metadata\MetadataTypeDescription;
use PKP\core\PKPApplication;

class Dc11Schema extends PKPDc11Schema
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Configure the DC schema.
        parent::__construct([PKPApplication::ASSOC_TYPE_SUBMISSION, MetadataTypeDescription::ASSOC_TYPE_ANY]);
    }
}
