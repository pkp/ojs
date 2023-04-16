<?php
/**
 * @file classes/components/form/submission/ReconfigureSubmission.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReconfigureSubmission
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring the submission wizard, such as the
 *   submission's section or language, after the submission was started.
 */

namespace APP\components\forms\submission;

use APP\publication\Publication;
use APP\section\Section;
use APP\submission\Submission;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\submission\ReconfigureSubmission as BaseReconfigureSubmission;
use PKP\context\Context;
use PKP\core\PKPString;

class ReconfigureSubmission extends BaseReconfigureSubmission
{
    public array $sections;

    /**
     * @param Section[] $sections
     */
    public function __construct(string $action, Submission $submission, Publication $publication, Context $context, array $sections)
    {
        parent::__construct($action, $submission, $publication, $context);

        $this->sections = $sections;

        if (count($this->sections) > 1) {
            $this->addSectionsField();
        }
    }

    protected function addSectionsField(): void
    {
        $this->addField(new FieldOptions('sectionId', [
            'type' => 'radio',
            'label' => __('section.section'),
            'description' => __('author.submit.journalSectionDescription'),
            'options' => $this->getSectionOptions(),
            'isRequired' => true,
            'value' => $this->publication->getData('sectionId'),
        ]));

        foreach ($this->sections as $section) {
            if (!trim(PKPString::html2text($section->getLocalizedPolicy()))) {
                continue;
            }
            $this->addField(new FieldHTML('sectionDescription' . $section->getId(), [
                'label' => $section->getLocalizedTitle(),
                'description' => $section->getLocalizedPolicy(),
                'showWhen' => ['sectionId', $section->getId()],
            ]));
        }
    }

    /**
     * Convert sections to options prop for a FieldOption
     */
    protected function getSectionOptions(): array
    {
        $options = [];
        foreach ($this->sections as $section) {
            $options[] = [
                'value' => $section->getId(),
                'label' => $section->getLocalizedTitle(),
            ];
        }
        return $options;
    }
}
