<?php

/**
 * @defgroup plugins_blocks_developedBy Developed By block plugin
 */

/**
 * @file plugins/blocks/developedBy/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_developedBy
 * @brief Wrapper for "developed by" block plugin.
 *
 */

require_once('DevelopedByBlockPlugin.inc.php');

return new DevelopedByBlockPlugin();


