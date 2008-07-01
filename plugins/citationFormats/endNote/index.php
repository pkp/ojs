<?php

/**
 * @defgroup plugins_citationFormats_endNote
 */
 
/**
 * @file plugins/citationFormats/endNote/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_endNote
 * @brief Wrapper for EndNote citation plugin.
 *
 */

// $Id$


require_once('EndNoteCitationPlugin.inc.php');

return new EndNoteCitationPlugin();

?>
