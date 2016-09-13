<?php

/**
 * @defgroup plugins_citationFormats_abnt ABNT Citation Format
 */
 
/**
 * @file plugins/citationFormats/abnt/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_abnt
 * @brief Wrapper for ABNT citation plugin.
 *
 */

require_once('AbntCitationPlugin.inc.php');

return new AbntCitationPlugin();

?>
