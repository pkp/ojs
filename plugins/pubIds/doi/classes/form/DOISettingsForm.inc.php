<?php

/**
 * @file plugins/pubIds/doi/classes/form/DOISettingsForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOISettingsForm
 * @ingroup plugins_pubIds_doi
 *
 * @brief Form for journal managers to setup DOI plugin
 */

use PKP\form\Form;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class DOISettingsForm extends Form
{
    //
    // Private properties
    //
    /** @var integer */
    public $_contextId;

    /**
     * Get the context ID.
     *
     * @return integer
     */
    public function _getContextId()
    {
        return $this->_contextId;
    }

    /** @var DOIPubIdPlugin */
    public $_plugin;

    /**
     * Get the plugin.
     *
     * @return DOIPubIdPlugin
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
     * @param $plugin DOIPubIdPlugin
     * @param $contextId integer
     */
    public function __construct($plugin, $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'doiObjects', 'required', 'plugins.pubIds.doi.manager.settings.doiObjectsRequired', function ($enableIssueDoi) use ($form) {
            return $form->getData('enableIssueDoi') || $form->getData('enablePublicationDoi') || $form->getData('enableRepresentationDoi');
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'doiPrefix', 'required', 'plugins.pubIds.doi.manager.settings.doiPrefixPattern', '/^10\.[0-9]{4,7}$/'));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'doiIssueSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiIssueSuffixPatternRequired', function ($doiIssueSuffixPattern) use ($form) {
            if ($form->getData('doiSuffix') == 'pattern' && $form->getData('enableIssueDoi')) {
                return $doiIssueSuffixPattern != '';
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'doiPublicationSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiPublicationSuffixPatternRequired', function ($doiPublicationSuffixPattern) use ($form) {
            if ($form->getData('doiSuffix') == 'pattern' && $form->getData('enablePublicationDoi')) {
                return $doiPublicationSuffixPattern != '';
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'doiRepresentationSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiRepresentationSuffixPatternRequired', function ($doiRepresentationSuffixPattern) use ($form) {
            if ($form->getData('doiSuffix') == 'pattern' && $form->getData('enableRepresentationDoi')) {
                return $doiRepresentationSuffixPattern != '';
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));

        // for DOI reset requests
        $request = Application::get()->getRequest();
        $this->setData('clearPubIdsLinkAction', new LinkAction(
            'reassignDOIs',
            new RemoteActionConfirmationModal(
                $request->getSession(),
                __('plugins.pubIds.doi.manager.settings.doiReassign.confirm'),
                __('common.delete'),
                $request->url(null, null, 'manage', null, ['verb' => 'clearPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds']),
                'modal_delete'
            ),
            __('plugins.pubIds.doi.manager.settings.doiReassign'),
            'delete'
        ));
        $this->setData('assignJournalWidePubIdsLinkAction', new LinkAction(
            'assignDOIs',
            new RemoteActionConfirmationModal(
                $request->getSession(),
                __('plugins.pubIds.doi.manager.settings.doiAssignJournalWide.confirm'),
                __('plugins.pubIds.doi.manager.settings.doiAssignJournalWide'),
                $request->url(null, null, 'manage', null, ['verb' => 'assignPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds']),
                'modal_confirm'
            ),
            __('plugins.pubIds.doi.manager.settings.doiAssignJournalWide'),
            'advance'
        ));
        $this->setData('pluginName', $plugin->getName());
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
        foreach ($this->_getFormFields() as $fieldName => $fieldType) {
            $this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
        }
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars(array_keys($this->_getFormFields()));
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->_getPlugin();
        $contextId = $this->_getContextId();
        foreach ($this->_getFormFields() as $fieldName => $fieldType) {
            $plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
        }
        parent::execute(...$functionArgs);
    }


    //
    // Private helper methods
    //
    public function _getFormFields()
    {
        return [
            'enableIssueDoi' => 'bool',
            'enablePublicationDoi' => 'bool',
            'enableRepresentationDoi' => 'bool',
            'doiPrefix' => 'string',
            'doiSuffix' => 'string',
            'doiIssueSuffixPattern' => 'string',
            'doiPublicationSuffixPattern' => 'string',
            'doiRepresentationSuffixPattern' => 'string',
        ];
    }
}
