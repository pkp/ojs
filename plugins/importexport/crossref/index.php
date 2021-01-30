<?php

/**
 * @defgroup plugins_importexport_crossref CrossRef Plugin
 */
 
/**
 * @file plugins/importexport/crossref/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_crossref
 * @brief Wrapper for CrossRef export plugin.
 *
 */

require_once('CrossRefExportPlugin.inc.php');

return new CrossRefExportPlugin();


