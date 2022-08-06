<?php

/**
 * @file plugins/importexport/native/filter/AuthorNativeXmlFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AuthorNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Author to a Native XML document.
 */

namespace APP\plugins\importexport\native\filter;

class AuthorNativeXmlFilter extends \PKP\plugins\importexport\native\filter\PKPAuthorNativeXmlFilter
{
    //
    // Implement template methods from PersistableFilter
    //
    /**
     * @copydoc PersistableFilter::getClassName()
     */
    public function getClassName()
    {
        return 'plugins.importexport.native.filter.AuthorNativeXmlFilter';
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\importexport\native\filter\AuthorNativeXmlFilter', '\AuthorNativeXmlFilter');
}
