<?php

/**
 * @file plugins/oaiMetadata/marcxml/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_oaiMetadata
 * @brief Wrapper for the OAI MARC21 format plugin.
 *
 */

// $Id$


require_once('OAIMetadataFormatPlugin_MARC21.inc.php');
require_once('OAIMetadataFormat_MARC21.inc.php');

return new OAIMetadataFormatPlugin_MARC21();

?>
