<?php

/**
 * @defgroup plugins_citationFormats_cbe
 */
 
/**
 * @file plugins/citationFormats/cbe/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_cbe
 * @brief Wrapper for CBE citation plugin.
 *
 */

require_once('CbeCitationPlugin.inc.php');

return new CbeCitationPlugin();

?>
