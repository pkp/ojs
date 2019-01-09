<?php
/**
 * @defgroup api_v1_site Site API requests
 */

/**
 * @file api/v1/site/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_site
 * @brief Handle API requests for the site object.
 */
import('api.v1.site.SiteHandler');
return new SiteHandler();
