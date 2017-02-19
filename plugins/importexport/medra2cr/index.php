<?php

/**
 * @defgroup plugins_importexport_medra2cr
 */

/**
 * @file plugins/importexport/medra2cr/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_medra2cr
 *
 * @brief Wrapper for the mEDRA2cr export plugin.
 */


require_once('Medra2crExportPlugin.inc.php');

return new Medra2crExportPlugin();

?>
