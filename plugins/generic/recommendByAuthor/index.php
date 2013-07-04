<?php

/**
 * @defgroup plugins_generic_recommendByAuthor
 */

/**
 * @file plugins/generic/recommendByAuthor/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_recommendByAuthor
 * @brief Wrapper for the "recommend articles from same author" plugin.
 *
 */

require_once('RecommendByAuthorPlugin.inc.php');

return new RecommendByAuthorPlugin();

?>
