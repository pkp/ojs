<?php

/**
 * @defgroup plugins_importexport_crossref
 */

/**
 * @file plugins/importexport/crossref/index.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_crossref
 *
 * @brief Wrapper for the CrossRef export plugin.
 */


require_once('CrossRefExportPlugin.inc.php');

return new CrossRefExportPlugin();

?>
