<?php

/**
 * @defgroup plugins_generic_recommendBySimilarity Recommend Similar Plugin
 */

/**
 * @file plugins/generic/recommendBySimilarity/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_recommendBySimilarity
 * @brief Wrapper for the "recommend similar articles" plugin.
 *
 */

require_once('RecommendBySimilarityPlugin.inc.php');

return new RecommendBySimilarityPlugin();


