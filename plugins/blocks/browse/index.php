<?php

/**
 * @defgroup plugins_blocks_browse Browse block plugin
 */

/**
 * @file plugins/blocks/browse/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_browse
 * @brief Wrapper for browse block plugin.
 *
 */


require_once('BrowseBlockPlugin.inc.php');

return new BrowseBlockPlugin();


