<?php

/**
 * @defgroup plugins_citationFormats_refMan
 */
 
/**
 * @file plugins/citationFormats/refMan/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_refMan
 * @brief Wrapper for ReferenceManager citation plugin.
 *
 */

require_once('RefManCitationPlugin.inc.php');

return new RefManCitationPlugin();

?>
