<?php

/**
 * @defgroup section Section
 * Implements sections.
 */

/**
 * @file classes/section/Section.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Section
 * @ingroup section
 *
 * @see DAO
 *
 * @brief Basic class describing a section.
*/

namespace APP\section;

class Section extends \PKP\section\PKPSection
{
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\section\Section', '\Section');
}
