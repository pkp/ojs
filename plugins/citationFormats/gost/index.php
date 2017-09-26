<?php

/**
 * @defgroup plugins_citationFormats_GOST GOST Citation Format
 */
 
/**
 * @file plugins/citationFormats/GOST/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_GOST
 * @brief Wrapper for GOST citation plugin.
 *
 */

require_once('GOSTCitationPlugin.inc.php');

return new GOSTCitationPlugin();

?>
