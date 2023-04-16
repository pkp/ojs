<?php
/**
 * @file classes/components/form/submission/StartSubmission.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StartSubmission
 *
 * @ingroup classes_controllers_form
 *
 * @brief The form to begin the submission wizard
 */

namespace APP\components\forms\submission;

use APP\section\Section;
use Illuminate\Support\Enumerable;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\submission\StartSubmission as BaseStartSubmission;
use PKP\context\Context;
use PKP\core\PKPString;

class StartSubmission extends BaseStartSubmission
{
    /**
     * @param Section[] $sections The sections that this user can submit to
     */
    public function __construct(string $action, Context $context, Enumerable $userGroups, array $sections)
    {
        parent::__construct($action, $context, $userGroups);

        if (count($sections) === 1) {
            $this->addHiddenField('sectionId', $sections[0]->getId());
        } else {
            $this->addField(new FieldOptions('sectionId', [
                'type' => 'radio',
                'label' => __('section.section'),
                'description' => __('author.submit.journalSectionDescription'),
                'options' => $this->getSectionOptions($sections),
                'value' => '',
                'isRequired' => true,
            ]), [FIELD_POSITION_AFTER, 'title']);

            foreach ($sections as $section) {
                if (!trim(PKPString::html2text($section->getLocalizedPolicy()))) {
                    continue;
                }
                $this->addField(new FieldHTML('sectionDescription' . $section->getId(), [
                    'label' => $section->getLocalizedTitle(),
                    'description' => $section->getLocalizedPolicy(),
                    'showWhen' => ['sectionId', $section->getId()],
                ]), [FIELD_POSITION_AFTER, 'sectionId']);
            }
        }
    }

    /**
     * Convert sections to options prop for a FieldOption
     *
     * @param Section[] $sections
     */
    protected function getSectionOptions(array $sections): array
    {
        $options = [];
        foreach ($sections as $section) {
            $options[] = [
                'value' => $section->getId(),
                'label' => $section->getLocalizedTitle(),
            ];
        }
        return $options;
    }
}
