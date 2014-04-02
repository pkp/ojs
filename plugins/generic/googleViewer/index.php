<?php

/**
 * @defgroup plugins_generic_googleViewer
 */

/**
 * @file plugins/generic/googleViewer/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_googleViewer
 * @brief Wrapper for custom locale plugin. Plugin based on Translator plugin.
 *
 */

require_once('GoogleViewerPlugin.inc.php');

return new GoogleViewerPlugin();

?>
