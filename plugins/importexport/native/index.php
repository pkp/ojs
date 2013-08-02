<?php

/**
 * @defgroup plugins_importexport_native Native XML Import/Export Plugin
 */
 
/**
 * @file plugins/importexport/native/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_native
 * @brief Wrapper for native XML import/export plugin.
 *
 */

require_once('NativeImportExportPlugin.inc.php');

return new NativeImportExportPlugin();

?>
