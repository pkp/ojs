<?php

/**
 * @defgroup plugins_importexport_doaj DOAJ Export Plugin
 */

/**
 * @file plugins/importexport/doaj/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_doaj
 * @brief Wrapper for DOAJ XML export plugin.
 *
 */

require_once('DOAJExportPlugin.inc.php');

return new DOAJExportPlugin();

?>
