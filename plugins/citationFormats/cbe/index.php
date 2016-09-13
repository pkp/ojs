<?php

/**
 * @defgroup plugins_citationFormats_cbe CBE Citation Format
 */
 
/**
 * @file plugins/citationFormats/cbe/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_cbe
 * @brief Wrapper for CBE citation plugin.
 *
 */

require_once('CbeCitationPlugin.inc.php');

return new CbeCitationPlugin();

?>
