<?php

/**
 * @defgroup plugins_importexport_medra
 */

/**
 * @file plugins/importexport/medra/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_medra
 *
 * @brief Wrapper for the mEDRA export plugin.
 */


require_once('MedraExportPlugin.inc.php');

return new MedraExportPlugin();

?>
