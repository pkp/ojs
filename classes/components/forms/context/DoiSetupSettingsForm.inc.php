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
use PKP\components\forms\FieldSelect;
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
            'options' => [
                [
                    'value' => Repo::doi()::TYPE_PUBLICATION,
                    'label' => __('doi.manager.settings.enableFor', ['objects' => __('doi.manager.settings.publications')]),
                ],
                [
                    'value' => Repo::doi()::TYPE_ISSUE,
                    'label' => __('doi.manager.settings.enableFor', ['objects' => __('issue.issues')]),
                ],
                [
                    'value' => Repo::doi()::TYPE_REPRESENTATION,
                    'label' => __('doi.manager.settings.enableFor', ['objects' => __('submission.layout.galleys')]),
                ]
            ],
            'value' => $context->getData(Context::SETTING_ENABLED_DOI_TYPES) ? $context->getData(Context::SETTING_ENABLED_DOI_TYPES) : [],
            'showWhen' => Context::SETTING_ENABLE_DOIS
        ]))
            ->addField(new FieldText(Context::SETTING_DOI_PREFIX, [
                'label' => __('doi.manager.settings.doiPrefix'),
                'description' => __('doi.manager.settings.doiPrefix.description'),
                'value' => $context->getData(Context::SETTING_DOI_PREFIX),
                'showWhen' => Context::SETTING_ENABLE_DOIS,
                'size' => 'small',
                'isRequired' => true

            ]))
            ->addField(new FieldSelect(Context::SETTING_DOI_CREATION_TIME, [
                'label' => __('doi.manager.settings.doiCreationTime.label'),
                'description' => __('doi.manager.settings.doiCreationTime.description'),
                'options' => [
                    [
                        'value' => Repo::doi()::CREATION_TIME_COPYEDIT,
                        'label' => __('doi.manager.settings.doiCreationTime.copyedit')
                    ],
                    [
                        'value' => Repo::doi()::CREATION_TIME_PUBLICATION,
                        'label' => __('doi.manager.settings.doiCreationTime.publication')
                    ],
                    [
                        'value' => Repo::doi()::CREATION_TIME_NEVER,
                        'label' => __('doi.manager.settings.doiCreationTime.never')
                    ]
                ],
                'value' => $context->getData(Context::SETTING_DOI_CREATION_TIME) ? $context->getData(Context::SETTING_DOI_CREATION_TIME) : Repo::doi()::CREATION_TIME_COPYEDIT,
                'showWhen' => Context::SETTING_ENABLE_DOIS

            ]))
// TODO: #doi Disabled until new default DOI pattern implemented
//            ->addField(new FieldOptions(Context::SETTING_USE_DEFAULT_DOI_SUFFIX, [
//                'label' => __('doi.manager.settings.doiSuffix'),
//                'description' => __('doi.manager.settings.doiSuffix.description'),
//                'options' => [
//                    [
//                        'value' => true,
//                        'label' => __('common.yes')
//                    ],
//                    [
//                        'value' => false,
//                        'label' => __('common.no')
//                    ]
//                ],
//                'value' => $context->getData(Context::SETTING_USE_DEFAULT_DOI_SUFFIX) !== null ? $context->getData(Context::SETTING_USE_DEFAULT_DOI_SUFFIX) : true,
//                'type' => 'radio',
//                'showWhen' => Context::SETTING_ENABLE_DOIS
//            ]))
            ->addField(new FieldOptions(Context::SETTING_CUSTOM_DOI_SUFFIX_TYPE, [
                'label' => __('doi.manager.settings.doiSuffix'),
                'description' => __('doi.manager.settings.doiSuffix.description'),
                'options' => [
                    [
                        'value' => Repo::doi()::SUFFIX_ISSUE,
                        'label' => __('doi.manager.settings.doiSuffixLegacy')
                    ],
                    [
                        'value' => Repo::doi()::CUSTOM_SUFFIX_MANUAL,
                        'label' => __('doi.manager.settings.doiSuffixCustomIdentifier')
                    ],
                    [
                        'value' => Repo::doi()::SUFFIX_CUSTOM_PATTERN,
                        'label' => __('doi.manager.settings.doiSuffixLegacyUser')
                    ],
                ],
                'value' => $context->getData(Context::SETTING_CUSTOM_DOI_SUFFIX_TYPE) ? $context->getData(Context::SETTING_CUSTOM_DOI_SUFFIX_TYPE) : Repo::doi()::SUFFIX_ISSUE,
                'type' => 'radio',
                'showWhen' => Context::SETTING_ENABLE_DOIS,
            ]));
    }
}
