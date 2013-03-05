<?php

/**
 * @defgroup plugins_blocks_mostRead
 */

/**
 * @file plugins/blocks/mostRead/index.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_mostRead
 * @brief Wrapper for "most read articles" block plugin.
 *
 */

require_once('MostReadBlockPlugin.inc.php');

return new MostReadBlockPlugin();

?>
