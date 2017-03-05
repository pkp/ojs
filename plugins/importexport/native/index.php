<?php

/**
 * @file plugins/importexport/native/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_native
 * @brief Wrapper for XML native import/export plugin.
 *
 */

require_once('NativeImportExportPlugin.inc.php');

return new NativeImportExportPlugin();

?>
