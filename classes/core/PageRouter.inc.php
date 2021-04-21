<?php

/**
 * @file classes/core/PageRouter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PageRouter
 * @ingroup core
 *
 * @brief Class providing OJS-specific page routing.
 */

namespace APP\core;

class PageRouter extends \PKP\core\PKPPageRouter
{
    /**
     * get the cacheable pages
     *
     * @return array
     */
    public function getCacheablePages()
    {
        return ['about', 'announcement', 'help', 'index', 'information', 'issue', ''];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\core\PageRouter', '\PageRouter');
}
