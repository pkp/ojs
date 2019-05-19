<?php

/**
 * @defgroup plugins_importexport_medra MEDRA Export Plugin
 */

/**
 * @file plugins/importexport/medra/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_medra
 *
 * @brief Wrapper for the mEDRA export plugin.
 */


require_once('MedraExportPlugin.inc.php');

return new MedraExportPlugin();


