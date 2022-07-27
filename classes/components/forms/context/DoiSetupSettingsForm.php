<?php
/**
 * @file classes/components/form/context/DoiSettingsForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiSettingsForm
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

class DoiSetupSettingsForm extends PKPDoiSetupSettingsForm
{
    public function __construct(string $action, array $locales, Context $context)
    {
        parent::__construct($action, $locales, $context);

        $this->addField(new FieldOptions(Context::SETTING_ENABLED_DOI_TYPES, [
            'label' => __('doi.manager.settings.doiObjects'),
            'description' => __('doi.manager.settings.doiObjectsRequired'),
            'groupId' => self::DOI_SETTINGS_GROUP,
            'options' => [
                [
                    'value' => Repo::doi()::TYPE_PUBLICATION,
                    'label' => __('article.articles'),
                ],
                [
                    'value' => Repo::doi()::TYPE_ISSUE,
                    'label' => __('issue.issues'),
                ],
                [
                    'value' => Repo::doi()::TYPE_REPRESENTATION,
                    'label' => __('doi.manager.settings.galleysWithDescription'),
                ]
            ],
            'value' => $context->getData(Context::SETTING_ENABLED_DOI_TYPES) ? $context->getData(Context::SETTING_ENABLED_DOI_TYPES) : [],
        ]), [FIELD_POSITION_BEFORE, Context::SETTING_DOI_PREFIX])
            ->addField(new FieldText(Repo::doi()::CUSTOM_ISSUE_PATTERN, [
                'label' => __('issue.issues'),
                'groupId' => self::DOI_CUSTOM_SUFFIX_GROUP,
                'value' => $context->getData(Repo::doi()::CUSTOM_ISSUE_PATTERN),
            ]));
    }
}
