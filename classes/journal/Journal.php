<?php

/**
 * @defgroup journal Journal
 * Extensions to the pkp-lib "context" concept to specialize it for use in OJS
 * in representing Journal objects and journal-specific concerns.
 */

/**
 * @file classes/journal/Journal.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Journal
 *
 * @ingroup journal
 *
 * @see JournalDAO
 *
 * @brief Describes basic journal properties.
 */

namespace APP\journal;

use APP\core\Application;
use PKP\context\Context;
use PKP\db\DAORegistry;

class Journal extends Context
{
    public const PUBLISHING_MODE_OPEN = 0;
    public const PUBLISHING_MODE_SUBSCRIPTION = 1;
    public const PUBLISHING_MODE_NONE = 2;

    //
    // Get/set methods
    //

    /**
     * Get the association type for this context.
     *
     * @return int
     */
    public function getAssocType()
    {
        return Application::ASSOC_TYPE_JOURNAL;
    }

    /**
     * @copydoc \PKP\core\DataObject::getDAO()
     */
    public function getDAO(): JournalDAO
    {
        return DAORegistry::getDAO('JournalDAO');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\journal\Journal', '\Journal');
    foreach ([
        'PUBLISHING_MODE_OPEN',
        'PUBLISHING_MODE_SUBSCRIPTION',
        'PUBLISHING_MODE_NONE',
    ] as $constantName) {
        define($constantName, constant('\Journal::' . $constantName));
    }
}
