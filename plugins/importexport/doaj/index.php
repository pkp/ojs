<?php

/**
 * @defgroup plugins_importexport_native
 */
 
/**
 * @file plugins/importexport/doaj/index.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_native
 * @brief Wrapper for native XML import/export plugin.
 *
 */

require_once('DOAJPlugin.inc.php');

return new DOAJPlugin();

?>
