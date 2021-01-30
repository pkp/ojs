<?php

/**
 * @file plugins/oaiMetadataFormats/marc/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_oaiMetadata
 * @brief Wrapper for the OAI MARC format plugin.
 *
 */

require_once('OAIMetadataFormatPlugin_MARC.inc.php');
require_once('OAIMetadataFormat_MARC.inc.php');

return new OAIMetadataFormatPlugin_MARC();


