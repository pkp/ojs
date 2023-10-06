<?php

namespace APP\components\forms\context;

use APP\core\Application;
use APP\orcid\OrcidManager;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;
use PKP\context\Context;
use PKP\facades\Locale;

class OrcidSettingsForm extends FormComponent
{
    public const ORCID_DEFAULT_GROUP = 'orcidDefaultGroup';
    public const ORCID_SETTINGS_GROUP = 'orcidSettingsGroup';
    public $id = 'orcidSettings';
    public $method = 'PUT';
    public Context $context;

    public function __construct(string $action, array $locales, \PKP\context\Context $context)
    {
        $this->action = $action;
        $this->locales = $locales;
        $this->context = $context;

        $this->addGroup(['id' => self::ORCID_DEFAULT_GROUP])
            ->addGroup([
                'id' => self::ORCID_SETTINGS_GROUP,
                'showWhen' => OrcidManager::ENABLED
            ])
            // TODO: Handle disabling form requirement when ORCID functionality is disabled.
            ->addField(new FieldOptions(OrcidManager::ENABLED, [
                'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath'),
                'groupId' => self::ORCID_DEFAULT_GROUP,
                'options' => [
                    // TODO: Remove temporary hard-coded string
                    ['value' => true, 'label' => 'Enable ORCID Profile functionality']
                ],
                'value' => (bool) $context->getData(OrcidManager::ENABLED) ?? false
            ]))
            ->addField(new FieldHTML('settingsDescription', [
                'groupId' => self::ORCID_DEFAULT_GROUP,
                'description' => __('orcidProfile.manager.settings.description'),
            ]));

        // ORCID API settings can be configured globally via config file or from this settings form
        if (OrcidManager::isGloballyConfigured()) {
            $site = Application::get()->getRequest()->getSite();

            $this->addField(new FieldHTML(OrcidManager::API_TYPE, [
                'groupId' => self::ORCID_SETTINGS_GROUP,
                'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath'),
                'description' => $this->getLocalizedApiTypeString($site->getData(OrcidManager::API_TYPE))
            ]))
                ->addField(new FieldHTML(OrcidManager::CLIENT_ID, [
                    'groupId' => self::ORCID_SETTINGS_GROUP,
                    'label' => __('orcidProfile.manager.settings.orcidClientId'),
                    'description' => $site->getData(OrcidManager::CLIENT_ID),
                ]))
                ->addField(new FieldHTML(OrcidManager::CLIENT_SECRET, [
                    'groupId' => self::ORCID_SETTINGS_GROUP,
                    'label' => __('orcidProfile.manager.settings.orcidClientSecret'),
                    'description' => $site->getData(OrcidManager::CLIENT_SECRET),
                ]));

        } else {
            $this->addField(new FieldSelect(OrcidManager::API_TYPE, [
                'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath'),
                'groupId' => self::ORCID_SETTINGS_GROUP,
                'isRequired' => true,
                'options' => [
                    ['value' => OrcidManager::API_PUBLIC_PRODUCTION, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.public')],
                    ['value' => OrcidManager::API_PUBLIC_SANDBOX, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.publicSandbox')],
                    ['value' => OrcidManager::API_MEMBER_PRODUCTION, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.member')],
                    ['value' => OrcidManager::API_MEMBER_SANDBOX, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.memberSandbox')],
                ],
                'value' => $context->getData(OrcidManager::API_TYPE) ?? OrcidManager::API_PUBLIC_PRODUCTION,
            ]))
                ->addField(new FieldText(OrcidManager::CLIENT_ID, [
                    'label' => __('orcidProfile.manager.settings.orcidClientId'),
                    'groupId' => self::ORCID_SETTINGS_GROUP,
                    'isRequired' => true,
                    'value' => $context->getData(OrcidManager::CLIENT_ID) ?? '',
                ]))
                ->addField(new FieldText(OrcidManager::CLIENT_SECRET, [
                    'label' => __('orcidProfile.manager.settings.orcidClientSecret'),
                    'groupId' => self::ORCID_SETTINGS_GROUP,
                    'isRequired' => true,
                    'value' => $context->getData(OrcidManager::CLIENT_SECRET) ?? '',
                ]));
        }

        // TODO: Labeled as OJS-specific in settingsForm.tpl. Check status
        $countries = [];
        foreach (Locale::getCountries() as $country) {
            $countries[] = [
                'value' => $country->getAlpha2(),
                'label' => $country->getLocalName(),
            ];
        }
        usort($countries, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        $this->addField(new FieldSelect(OrcidManager::COUNTRY, [
            'groupId' => self::ORCID_SETTINGS_GROUP,
            'label' => __('orcidProfile.manager.settings.country'),
            'description' => __('orcidProfile.manager.settings.review.help'),
            'options' => $countries,
            'value' => $context->getData(OrcidManager::COUNTRY) ?? '',
        ]))
            ->addField(new FieldText(OrcidManager::CITY, [
                'groupId' => self::ORCID_SETTINGS_GROUP,
                'label' => 'orcidProfile.manager.settings.city',
                'value' => $context->getData(OrcidManager::CITY) ?? '',
            ]))
            ->addField(new FieldOptions(OrcidManager::SEND_MAIL_TO_AUTHORS_ON_PUBLICATION, [
                'groupId' => self::ORCID_SETTINGS_GROUP,
                'label' => __('orcidProfile.manager.settings.mailSectionTitle'),
                'options' => [
                    ['value' => true, 'label' => __('orcidProfile.manager.settings.sendMailToAuthorsOnPublication')]
                ],
                'value' => (bool) $context->getData(OrcidManager::SEND_MAIL_TO_AUTHORS_ON_PUBLICATION) ?? false,
            ]))
            ->addField(new FieldSelect(OrcidManager::LOG_LEVEL, [
                'groupId' => self::ORCID_SETTINGS_GROUP,
                'label' => __('orcidProfile.manager.settings.logSectionTitle'),
                'description' => __('orcidProfile.manager.settings.logLevel.help'),
                'options' => [
                    ['value' => 'ERROR', 'label' => __('orcidProfile.manager.settings.logLevel.error')],
                    ['value' => 'ALL', 'label' => __('orcidProfile.manager.settings.logLevel.all')],
                ],
                'value' => $context->getData(OrcidManager::LOG_LEVEL) ?? 'ERROR',
            ]));
    }

    private function getLocalizedApiTypeString(string $apiType): string
    {
        return match ($apiType) {
            OrcidManager::API_PUBLIC_PRODUCTION => __('orcidProfile.manager.settings.orcidProfileAPIPath.public'),
            OrcidManager::API_PUBLIC_SANDBOX => __('orcidProfile.manager.settings.orcidProfileAPIPath.publicSandbox'),
            OrcidManager::API_MEMBER_PRODUCTION => __('orcidProfile.manager.settings.orcidProfileAPIPath.member'),
            OrcidManager::API_MEMBER_SANDBOX => __('orcidProfile.manager.settings.orcidProfileAPIPath.memberSandbox'),
        };
    }
}
