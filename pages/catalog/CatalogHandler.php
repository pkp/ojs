<?php

/**
 * @file pages/catalog/CatalogHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CatalogHandler
 *
 * @ingroup pages_catalog
 *
 * @brief Redirect requests to old catalog handler
 */

namespace APP\pages\catalog;

use APP\handler\Handler;

class CatalogHandler extends Handler
{
    /**
     * Redirect calls to category
     * https://github.com/pkp/pkp-lib/issues/5932
     *
     * @deprecated 3.6
     *
     */
    public function category($args, $request)
    {
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, 'articles', 'category', $args);
    }

    /**
     * Redirect calls to full sized image for a category.
     * https://github.com/pkp/pkp-lib/issues/5932
     *
     * @deprecated 3.6
     *
     */

    public function fullSize($args, $request)
    {
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, 'articles', 'fullSize', [$args[0]], ['type' => $request->getUserVar('type')]);
    }

    /**
     * Redirect calls to thumbnail for a category.
     * https://github.com/pkp/pkp-lib/issues/5932
     *
     * @deprecated 3.6
     *
     */
    public function thumbnail($args, $request)
    {
        header('HTTP/1.1 301 Moved Permanently');
        $request->redirect(null, 'articles', 'thumbnail', [$args[0]], ['type' => $request->getUserVar('type')]);
    }
}
