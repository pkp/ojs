<?php

/**
 * @defgroup metsPlugin METS Plugin
 * Implements the METS import/export plugin.
 */

/**
 * @file plugins/importexport/mets/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup metsPlugin
 * @brief Wrapper for METS export plugin.
 */

require_once('MetsExportPlugin.inc.php');

return new METSExportPlugin();

?>
