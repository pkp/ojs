<?php

/**
 * @file plugins/generic/doaj/classes/form/DOAJSettingsForm.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJSettingsForm
 *
 * @brief Form for journal managers to setup DOAJ plugin
 */

namespace APP\plugins\generic\doaj\classes\form;

use APP\plugins\PubObjectsExportSettingsForm;

class DOAJSettingsForm extends PubObjectsExportSettingsForm
{
    //
    // Private properties
    //
    /** @var int */
    public $_contextId;

    /**
     * Get the context ID.
     *
     * @return int
     */
    public function _getContextId()
    {
        return $this->_contextId;
    }

    /** @var \PKP\plugins\Plugin */
    public $_plugin;

    /**
     * Get the plugin.
     *
     * @return \PKP\plugins\Plugin
     */
    public function _getPlugin()
    {
        return $this->_plugin;
    }


    //
    // Constructor
    //
    /**
     * Constructor
     *
     * @param \PKP\plugins\Plugin $plugin
     * @param int $contextId
     */
    public function __construct($plugin, $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        // Add form validation checks.
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }


    //
    // Implement template methods from Form
    //
    /**
     * @copydoc Form::initData()
     */
    public function initData()
    {
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        foreach ($this->getFormFields() as $fieldName => $fieldType) {
            $this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
        }
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars(array_keys($this->getFormFields()));
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->_getPlugin();
        $contextId = $this->_getContextId();
        parent::execute(...$functionArgs);
        foreach ($this->getFormFields() as $fieldName => $fieldType) {
            $plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
        }
    }

    /**
     * @copydoc PubObjectsExportSettingsForm::isOptional()
     */
    public function getFormFields(): array
    {
        return [
            'apiKey' => 'string',
            'automaticRegistration' => 'bool',
        ];
    }

    /**
     * @copydoc PubObjectsExportSettingsForm::isOptional()
     */
    public function isOptional($settingName): bool
    {
        return in_array($settingName, ['apiKey', 'automaticRegistration']);
    }
}
