<?php

/**
 * @defgroup plugins_citationFormats_apa
 */
 
/**
 * @file plugins/citationFormats/apa/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_apa
 * @brief Wrapper for APA citation plugin.
 *
 */

require_once('ApaCitationPlugin.inc.php');

return new ApaCitationPlugin();

?>
