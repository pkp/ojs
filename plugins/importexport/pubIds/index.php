<?php

/**
 * @defgroup plugins_importexport_pubIds
 */

/**
 * @file plugins/importexport/pubIds/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_pubIds
 * @brief Wrapper for public identifiers XML import/export plugin.
 *
 */
require_once('PubIdImportExportPlugin.inc.php');

return new PubIdImportExportPlugin();

?>
