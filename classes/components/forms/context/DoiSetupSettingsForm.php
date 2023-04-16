<?php
/**
 * @file classes/components/form/context/DoiSetupSettingsForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiSetupSettingsForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for enabling and configuring DOI settings for a given context
 */

namespace APP\components\forms\context;

use APP\facades\Repo;
use PKP\components\forms\context\PKPDoiSetupSettingsForm;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldText;
use PKP\context\Context;
use PKP\plugins\Hook;

class DoiSetupSettingsForm extends PKPDoiSetupSettingsForm
{
    public function __construct(string $action, array $locales, Context $context)
    {
        parent::__construct($action, $locales, $context);

        $this->objectTypeOptions = [
            [
                'value' => Repo::doi()::TYPE_PUBLICATION,
                'label' => __('article.articles'),
                'allowedBy' => [],
            ],
            [
                'value' => Repo::doi()::TYPE_ISSUE,
                'label' => __('issue.issues'),
                'allowedBy' => [],
            ],
            [
                'value' => Repo::doi()::TYPE_REPRESENTATION,
                'label' => __('doi.manager.settings.galleysWithDescription'),
                'allowedBy' => [],
            ]
        ];
        Hook::call('DoiSetupSettingsForm::getObjectTypes', [&$this->objectTypeOptions]);
        if ($this->enabledRegistrationAgency === null) {
            $filteredOptions = $this->objectTypeOptions;
        } else {
            $filteredOptions = array_filter($this->objectTypeOptions, function ($option) {
                return in_array($this->enabledRegistrationAgency, $option['allowedBy']);
            });
        }


        $this->addField(new FieldOptions(Context::SETTING_ENABLED_DOI_TYPES, [
            'label' => __('doi.manager.settings.doiObjects'),
            'description' => __('doi.manager.settings.doiObjectsRequired'),
            'groupId' => self::DOI_SETTINGS_GROUP,
            'options' => $filteredOptions,
            'value' => $context->getData(Context::SETTING_ENABLED_DOI_TYPES) ? $context->getData(Context::SETTING_ENABLED_DOI_TYPES) : [],
        ]), [FIELD_POSITION_BEFORE, Context::SETTING_DOI_PREFIX])
            ->addField(new FieldText(Repo::doi()::CUSTOM_ISSUE_PATTERN, [
                'label' => __('issue.issues'),
                'groupId' => self::DOI_CUSTOM_SUFFIX_GROUP,
                'value' => $context->getData(Repo::doi()::CUSTOM_ISSUE_PATTERN),
            ]));
    }
}
