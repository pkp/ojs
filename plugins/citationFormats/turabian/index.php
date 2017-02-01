<?php

/**
 * @defgroup plugins_citationFormats_turabian Turabian Citation Format
 */
 
/**
 * @file plugins/citationFormats/turabian/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_turabian
 * @brief Wrapper for Turabian citation plugin.
 *
 */

require_once('TurabianCitationPlugin.inc.php');

return new TurabianCitationPlugin();

?>
