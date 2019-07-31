<?php

/**
 * @defgroup plugins_generic_metadataExport
 */
 
/**
 * @file plugins/generic/metadataExport/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_metadataExport
 * @brief Wrapper for Metadata Export plugin. Based on Web Feed Plugin.
 *
 */

require_once('MetadataExportPlugin.inc.php');

return new MetadataExportPlugin(); 

?> 
