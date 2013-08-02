<?php

/**
 * @defgroup plugins_citationFormats_refWorks RefWorks Citation Format
 */
 
/**
 * @file plugins/citationFormats/refWorks/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_refWorks
 * @brief Wrapper for RefWorks citation plugin.
 *
 */

require_once('RefWorksCitationPlugin.inc.php');

return new RefWorksCitationPlugin();

?>
