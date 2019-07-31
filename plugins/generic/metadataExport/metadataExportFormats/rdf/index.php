<?php

/**
 * @defgroup plugins_generic_metadataExport_metadataExportFormats_rdf
 */
 
/**
 * @file plugins/generic/metadataExport/metadataExportFormats/rdf/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_rdf
 * @brief Wrapper for RDF metadata export plugin.
 *
 */

require_once('RdfMetadataExportPlugin.inc.php');

return new RdfMetadataExportPlugin();
?>