<?php

/**
 * @defgroup plugins_generic_metadataExport_metadataExportFormats_ris
 */
 
/**
 * @file plugins/generic/metadataExport/metadataExportFormats/ris/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_ris
 * @brief Wrapper for RIS metadata export plugin.
 *
 */

require_once('RisMetadataExportPlugin.inc.php');

return new RisMetadataExportPlugin();
?>