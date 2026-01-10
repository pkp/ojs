<?php

/**
 * @file pages/search/SearchHandler.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SearchHandler
 *
 * @brief Handle search requests.
 */

namespace APP\pages\search;

use APP\security\authorization\OjsJournalMustPublishPolicy;

class SearchHandler extends \PKP\pages\search\SearchHandler
{
    /**
     * @copydoc PKPHandler::authorize()
     *
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        if ($request->getContext()) {
            $this->addPolicy(new OjsJournalMustPublishPolicy($request));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }
}
