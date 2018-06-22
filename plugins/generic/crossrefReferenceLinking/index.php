<?php

/**
 * @defgroup plugins_generic_crossrefReferenceLinking
 */

/**
 * @file plugins/generic/crossrefReferenceLinking/index.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_crossrefReferenceLinking
 * @brief Wrapper for Crossref Reference Linking plugin.
 *
 */
require_once('CrossrefReferenceLinkingPlugin.inc.php');

return new CrossrefReferenceLinkingPlugin();

?>
