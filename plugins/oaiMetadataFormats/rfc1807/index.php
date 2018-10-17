<?php

/**
 * @file plugins/oaiMetadataFormats/rfc1807/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_oaiMetadata
 * @brief Wrapper for the OAI RFC1807 format plugin.
 *
 */

require_once('OAIMetadataFormatPlugin_RFC1807.inc.php');
require_once('OAIMetadataFormat_RFC1807.inc.php');

return new OAIMetadataFormatPlugin_RFC1807();


