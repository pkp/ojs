<?php

/**
 * @file plugins/generic/datacite/classes/DataciteSetting.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteSettings
 *
 * @ingroup plugins_generic_datacite_classes
 *
 * @brief Setting management class to handle schema, fields, validation, etc. for Datacite plugin
 */

namespace APP\plugins\generic\datacite\classes;

use Illuminate\Validation\Validator;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldText;
use PKP\context\Context;

class DataciteSettings extends \PKP\doi\RegistrationAgencySettings
{
    public function getSchema(): \stdClass
    {
        return (object) [
            'title' => 'Datacite Plugin',
            'description' => 'Registration agency plugin for Datacite',
            'type' => 'object',
            'required' => [],
            'properties' => (object) [

                'username' => (object) [
                    'type' => 'string',
                    'validation' => ['nullable', 'max:50']
                ],
                'password' => (object) [
                    'type' => 'string',
                    'validation' => ['nullable', 'max:50']
                ],
                'testMode' => (object) [
                    'type' => 'boolean',
                    'validation' => ['nullable']
                ],
                'testUsername' => (object) [
                    'type' => 'string',
                    'validation' => ['nullable', 'max:50']
                ],
                'testPassword' => (object) [
                    'type' => 'string',
                    'validation' => ['nullable', 'max:50']
                ],
                'testDOIPrefix' => (object) [
                    'type' => 'string',
                    'validation' => ['nullable', 'max:50']
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFields(Context $context): array
    {
        return [
            new FieldHTML('preamble', [
                'label' => __('plugins.importexport.datacite.settings.label'),
                'description' => $this->_getPreambleText(),
            ]),
            new FieldText('username', [
                'label' => __('plugins.importexport.datacite.settings.form.username'),
                'value' => $this->agencyPlugin->getSetting($context->getId(), 'username'),
            ]),
            new FieldText('password', [
                'label' => __('plugins.importexport.common.settings.form.password'),
                'description' => __('plugins.importexport.common.settings.form.password.description'),
                'inputType' => 'password',
                'value' => $this->agencyPlugin->getSetting($context->getId(), 'password'),
            ]),
            new FieldOptions('testMode', [
                'label' => __('plugins.importexport.common.settings.form.testMode.label'),
                'options' => [
                    ['value' => true, 'label' => __('plugins.importexport.datacite.settings.form.testMode.description')],
                ],
                'value' => $this->agencyPlugin->getSetting($context->getId(), 'testMode'),
            ]),
            new FieldText('testUsername', [
                'label' => __('plugins.importexport.datacite.settings.form.testUsername'),
                'value' => $this->agencyPlugin->getSetting($context->getId(), 'testUsername'),
            ]),
            new FieldText('testPassword', [
                'label' => __('plugins.importexport.datacite.settings.form.testPassword'),
                'description' => __('plugins.importexport.common.settings.form.password.description'),
                'inputType' => 'password',
                'value' => $this->agencyPlugin->getSetting($context->getId(), 'testPassword'),
            ]),
            new FieldText('testDOIPrefix', [
                'label' => __('plugins.importexport.datacite.settings.form.testDOIPrefix'),
                'value' => $this->agencyPlugin->getSetting($context->getId(), 'testDOIPrefix'),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addValidationChecks(Validator &$validator, $props): void
    {
        // If in test mode, the test DOI prefix must be set
        $validator->after(function (Validator $validator) use ($props) {
            if ($props['testMode']) {
                if (empty($props['testDOIPrefix'])) {
                    $validator->errors()->add('testDOIPrefix', __('plugins.importexport.datacite.settings.form.testDOIPrefixRequired'));
                }
            }
        });

        // If username exists, there will be the possibility to register from within OJS,
        // so the test username must exist too
        $validator->after(function (Validator $validator) use ($props) {
            if (!empty($props['username']) && empty($props['testUsername'])) {
                $validator->errors()->add('testUsername', __('plugins.importexport.datacite.settings.form.testUsernameRequired'));
            }
        });
    }

    protected function _getPreambleText(): string
    {
        $text = '';
        $text .= '<p>' . __('plugins.importexport.datacite.settings.description') . '</p>';
        $text .= '<p>' . __('plugins.importexport.datacite.intro') . '</p>';

        return $text;
    }
}
