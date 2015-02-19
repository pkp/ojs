<?php

/**
 * @defgroup plugins_citationFormats_proCite ProCite Citation Format
 */
 
/**
 * @file plugins/citationFormats/proCite/index.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_proCite
 * @brief Wrapper for ProCite citation plugin.
 *
 */

require_once('ProCiteCitationPlugin.inc.php');

return new ProCiteCitationPlugin();

?>
