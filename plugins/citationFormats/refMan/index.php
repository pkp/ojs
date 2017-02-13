<?php

/**
 * @defgroup plugins_citationFormats_refMan RefMan Citation Format
 */
 
/**
 * @file plugins/citationFormats/refMan/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_refMan
 * @brief Wrapper for ReferenceManager citation plugin.
 *
 */

require_once('RefManCitationPlugin.inc.php');

return new RefManCitationPlugin();

?>
