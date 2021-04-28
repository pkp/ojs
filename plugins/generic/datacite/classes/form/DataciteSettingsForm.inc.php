<?php

/**
 * @file plugins/generic/datacite/classes/form/DataciteSettingsForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteSettingsForm
 * @ingroup plugins_generic_datacite_classes_form
 *
 * @brief Form for journal managers to setup the DataCite plugin.
 */

use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class DataciteSettingsForm extends Form
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

    /** @var DataciteExportPlugin */
    public $_plugin;

    /**
     * Get the plugin.
     *
     * @return DataciteExportPlugin
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
     * @param DataciteExportPlugin $plugin
     * @param int $contextId
     */
    public function __construct($plugin, $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        // DOI plugin settings action link
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
        if (isset($pubIdPlugins['doipubidplugin'])) {
            $application = Application::get();
            $request = $application->getRequest();
            $dispatcher = $application->getDispatcher();
            $doiPluginSettingsLinkAction = new LinkAction(
                'settings',
                new AjaxModal(
                    $dispatcher->url($request, PKPApplication::ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'manage', null, ['plugin' => 'doipubidplugin', 'category' => 'pubIds']),
                    __('plugins.importexport.common.settings.DOIPluginSettings')
                ),
                __('plugins.importexport.common.settings.DOIPluginSettings'),
                null
            );
            $this->setData('doiPluginSettingsLinkAction', $doiPluginSettingsLinkAction);
        }

        // Add form validation checks.
        // The username is used in HTTP basic authentication and according to RFC2617 it therefore may not contain a colon.
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'username', FormValidator::FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.importexport.datacite.settings.form.usernameRequired', '/^[^:]+$/'));
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
     * @copydoc Form::validate
     */
    public function validate($callHooks = true)
    {
        // if in test mode, the test DOI prefix must exist
        if ($this->getData('testMode')) {
            if (empty($this->getData('testDOIPrefix'))) {
                $this->addError('testDOIPrefix', __('plugins.importexport.datacite.settings.form.testDOIPrefixRequired'));
                $this->addErrorField('testDOIPrefix');
            }
            // if username exist there will be the possibility to register from within OJS,
            // so the test username must exist too
            if (!empty($this->getData('username')) && empty($this->getData('testUsername'))) {
                $this->addError('testUsername', __('plugins.importexport.datacite.settings.form.testUsernameRequired'));
                $this->addErrorField('testUsername');
            }
        }

        return parent::validate($callHooks);
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


    //
    // Public helper methods
    //
    /**
     * Get form fields
     *
     * @return array (field name => field type)
     */
    public function getFormFields()
    {
        return [
            'username' => 'string',
            'password' => 'string',
            'automaticRegistration' => 'bool',
            'testMode' => 'bool',
            'testUsername' => 'string',
            'testPassword' => 'string',
            'testDOIPrefix' => 'string',
        ];
    }

    /**
     * Is the form field optional
     *
     * @param string $settingName
     *
     * @return bool
     */
    public function isOptional($settingName)
    {
        return in_array($settingName, ['username', 'password', 'automaticRegistration', 'testMode', 'testUsername', 'testPassword', 'testDOIPrefix']);
    }
}
