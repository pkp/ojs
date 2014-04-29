<?php

/**
 * @defgroup plugins_blocks_keyword_cloud
 */
 
/**
 * @file plugins/blocks/keywordCloud/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_keyword_cloud
 * @brief Wrapper for keyword cloud block plugin.
 *
 */

require_once('KeywordCloudBlockPlugin.inc.php');

return new KeywordCloudBlockPlugin();

?> 
