<?php

/**
 * @defgroup api_v1_articles Article API requests
 */

/**
 * @file api/v1/articles/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_articles
 * @brief Handle requests for articles API functions.
 *
 */

import('api.v1.articles.ArticlesHandler');
return new ArticlesHandler();
