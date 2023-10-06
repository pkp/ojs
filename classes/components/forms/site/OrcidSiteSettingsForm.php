<?php

namespace APP\components\forms\site;

use APP\orcid\OrcidManager;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;
use PKP\site\Site;

class OrcidSiteSettingsForm extends FormComponent
{
    public $id = 'orcidSiteSettings';
    public $method = 'PUT';

    public function __construct(string $action, array $locales, Site $site)
    {
        parent::__construct($this->id, $this->method, $action, $locales);

        // TODO: How to handle all should be blank or completed (all or nothing)
        $this->addField(new FieldSelect(OrcidManager::API_TYPE, [
            'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath'),
            'options' => [
                ['value' => OrcidManager::API_PUBLIC_PRODUCTION, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.public')],
                ['value' => OrcidManager::API_PUBLIC_SANDBOX, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.publicSandbox')],
                ['value' => OrcidManager::API_MEMBER_PRODUCTION, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.member')],
                ['value' => OrcidManager::API_MEMBER_SANDBOX, 'label' => __('orcidProfile.manager.settings.orcidProfileAPIPath.memberSandbox')],
            ],
            'value' => $site->getData(OrcidManager::API_TYPE) ?? OrcidManager::API_PUBLIC_PRODUCTION,
        ]))
            ->addField(new FieldText(OrcidManager::CLIENT_ID, [
                'label' => __('orcidProfile.manager.settings.orcidClientId'),
                'value' => $site->getData(OrcidManager::CLIENT_ID) ?? '',
            ]))
            ->addField(new FieldText(OrcidManager::CLIENT_SECRET, [
                'label' => __('orcidProfile.manager.settings.orcidClientSecret'),
                'value' => $site->getData(OrcidManager::CLIENT_SECRET) ?? '',
            ]));
    }
}
