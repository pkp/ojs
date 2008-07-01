<?php

/**
 * @defgroup plugins_importexport_sample
 */
 
/**
 * @file plugins/importexport/sample/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_sample
 * @brief Wrapper for sample import/export plugin.
 *
 */

// $Id$


require_once('SampleImportExportPlugin.inc.php');

return new SampleImportExportPlugin();

?>
