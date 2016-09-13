<?php

/**
 * @defgroup plugins_importexport_medra MEDRA Export Plugin
 */

/**
 * @file plugins/importexport/medra/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_medra
 *
 * @brief Wrapper for the mEDRA export plugin.
 */


require_once('MedraExportPlugin.inc.php');

return new MedraExportPlugin();

?>
