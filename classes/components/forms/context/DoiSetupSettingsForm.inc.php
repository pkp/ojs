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
        ]));
    }
}
