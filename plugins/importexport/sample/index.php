<?php

/**
 * @defgroup plugins_importexport_sample Sample Import/Export Plugin
 */
 
/**
 * @file plugins/importexport/sample/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_sample
 * @brief Wrapper for sample import/export plugin.
 *
 */

require_once('SampleImportExportPlugin.inc.php');

return new SampleImportExportPlugin();


