<?php
/**
 * @defgroup plugins_generic_lensGalley eLife Lens Article Galley Plugin
 */

/**
 * @file plugins/generic/lensGalley/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_lensGalley
 * @brief Wrapper for eLife Lens article galley plugin.
 *
 */

require_once('LensGalleyPlugin.inc.php');

return new LensGalleyPlugin();

?>
