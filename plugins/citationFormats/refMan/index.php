<?php

/**
 * @defgroup plugins_citationFormats_refMan
 */
 
/**
 * @file plugins/citationFormats/refMan/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_refMan
 * @brief Wrapper for ReferenceManager citation plugin.
 *
 */

// $Id$


require_once('RefManCitationPlugin.inc.php');

return new RefManCitationPlugin();

?>
