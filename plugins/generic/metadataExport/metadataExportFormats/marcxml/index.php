<?php

/**
 * @defgroup plugins_generic_metadataExport_metadataExportFormats_marcxml
 */
 
/**
 * @file plugins/generic/metadataExport/metadataExportFormats/marcxml/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_marcxml
 * @brief Wrapper for MARC XML metadata export plugin.
 *
 */

require_once('MarcXMLMetadataExportPlugin.inc.php');

return new MarcXMLMetadataExportPlugin();
?>
