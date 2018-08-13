<?php

/**
 * @defgroup plugins_generic_recommendByAuthor Recommend Articles From The Same Author Plugin
 */

/**
 * @file plugins/generic/recommendByAuthor/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_recommendByAuthor
 * @brief Wrapper for the "recommend articles from same author" plugin.
 *
 */

require_once('RecommendByAuthorPlugin.inc.php');

return new RecommendByAuthorPlugin();

?>
