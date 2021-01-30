<?php

/**
 * @defgroup plugins_importexport_doaj DOAJ Export Plugin
 */

/**
 * @file plugins/importexport/doaj/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_doaj
 * @brief Wrapper for DOAJ XML export plugin.
 *
 */

require_once('DOAJExportPlugin.inc.php');

return new DOAJExportPlugin();


