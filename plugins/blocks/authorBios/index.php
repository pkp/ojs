<?php

/**
 * @defgroup plugins_blocks_author_bios
 */

/**
 * @file plugins/blocks/authorBios/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_author_bios
 * @brief Wrapper for author bios block plugin.
 *
 */

require_once('AuthorBiosBlockPlugin.inc.php');

return new AuthorBiosBlockPlugin();

?>
