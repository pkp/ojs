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
use PKP\facades\Locale;

class Journal extends Context
{
    public const PUBLISHING_MODE_OPEN = 0;
    public const PUBLISHING_MODE_SUBSCRIPTION = 1;
    public const PUBLISHING_MODE_NONE = 2;

    /**
     * Get "localized" journal page title (if applicable).
     *
     * @return string|null
     *
     * @deprecated 3.3.0, use getLocalizedData() instead
     */
    public function getLocalizedPageHeaderTitle()
    {
        $titleArray = $this->getData('name');

        foreach ([Locale::getLocale(), Locale::getPrimaryLocale()] as $locale) {
            if (isset($titleArray[$locale])) {
                return $titleArray[$locale];
            }
        }
        return null;
    }

    /**
     * Get "localized" journal page logo (if applicable).
     *
     * @return array|null
     *
     * @deprecated 3.3.0, use getLocalizedData() instead
     */
    public function getLocalizedPageHeaderLogo()
    {
        $logoArray = $this->getData('pageHeaderLogoImage');
        foreach ([Locale::getLocale(), Locale::getPrimaryLocale()] as $locale) {
            if (isset($logoArray[$locale])) {
                return $logoArray[$locale];
            }
        }
        return null;
    }

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
     *
     * @return JournalDAO
     */
    public function getDAO()
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
