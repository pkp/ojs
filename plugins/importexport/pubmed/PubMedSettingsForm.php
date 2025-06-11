<?php

/**
 * @file plugins/importexport/pubmed/classes/form/PubMedSettingsForm.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubMedSettingsForm
 *
 * @brief Form for journal managers to set up the PubMed export plugin
 */

namespace APP\plugins\importexport\pubmed;

use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\plugins\Plugin;

class PubMedSettingsForm extends Form
{
    public int $contextId;
    public Plugin $plugin;

    /**
     * Constructor
     */
    public function __construct(Plugin $plugin, int $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        // Add form validation checks.
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Get the context ID.
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * Get the plugin.
     */
    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    //
    // Implement template methods from Form
    //
    /**
     * @copydoc Form::initData()
     */
    public function initData(): void
    {
        $contextId = $this->getContextId();
        $plugin = $this->getPlugin();
        foreach ($this->getFormFields() as $fieldName => $fieldType) {
            $this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
        }
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData(): void
    {
        $this->readUserVars(array_keys($this->getFormFields()));
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->getPlugin();
        $contextId = $this->getContextId();
        parent::execute(...$functionArgs);
        foreach ($this->getFormFields() as $fieldName => $fieldType) {
            $plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
        }
    }

    /**
     * Get form fields
     *
     * @return array (field name => field type)
     */
    public function getFormFields(): array
    {
        return [
            'nlmTitle' => 'string',
        ];
    }

    /**
     * If the form field is optional
     */
    public function isOptional(string $settingName): bool
    {
        return $settingName == 'nlmTitle';
    }
}
