<?php

/**
 * @defgroup plugins_importexport_erudit
 */
 
/**
 * @file plugins/importexport/erudit/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_erudit
 * @brief Wrapper for Erudit XML export plugin.
 *
 */

// $Id$


require_once('EruditExportPlugin.inc.php');

return new EruditExportPlugin();

?>
