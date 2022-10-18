<?php
/**
 * @file classes/components/form/publication/TitleAbstractForm.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TitleAbstractForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's title and abstract
 */

namespace APP\components\forms\publication;

use APP\journal\Section;
use APP\publication\Publication;
use PKP\components\forms\publication\TitleAbstractForm as PKPTitleAbstractForm;

class TitleAbstractForm extends PKPTitleAbstractForm
{
    public Section $section;

    public function __construct(string $action, array $locales, Publication $publication, Section $section, bool $isSubmissionWizard = false)
    {
        $this->section = $section;
        $this->abstractWordLimit = $section->getData('wordCount')
            ? (int) $section->getData('wordCount')
            : 0;
        $this->isAbstractRequired = !$section->getData('abstractsNotRequired');

        parent::__construct($action, $locales, $publication, $isSubmissionWizard);
    }
}
