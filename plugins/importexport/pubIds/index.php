<?php

/**
 * @defgroup plugins_importexport_pubIds Pub ID Export Plugin
 */

/**
 * @file plugins/importexport/pubIds/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_pubIds
 * @brief Wrapper for public identifiers XML import/export plugin.
 *
 */
require_once('PubIdImportExportPlugin.inc.php');

return new PubIdImportExportPlugin();

?>
