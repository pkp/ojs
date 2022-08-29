<?php

/**
 * @defgroup i18n I18N
 * Implements localization concerns such as locale files, time zones, and country lists.
 */

/**
 * @file classes/i18n/AppLocale.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPLocale
 * @ingroup i18n
 *
 * @brief Deprecated class, kept only for backwards compatibility with external plugins
 */

namespace APP\i18n;

use PKP\i18n\PKPLocale;

if (!PKP_STRICT_MODE) {

    /**
     * @deprecated The class \APP\i18n\AppLocale has been replaced by PKP\facades\Locale
     */
    class AppLocale extends PKPLocale
    {
    }

    class_alias('\APP\i18n\AppLocale', '\AppLocale');
}
