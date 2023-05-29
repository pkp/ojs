<?php

/**
 * @file plugins/pubIds/urn/classes/form/URNSettingsForm.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class URNSettingsForm
 *
 * @brief Form for journal managers to setup URN plugin
 */

namespace APP\plugins\pubIds\urn\classes\form;

use APP\core\Application;
use APP\plugins\pubIds\urn\URNPubIdPlugin;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class URNSettingsForm extends Form
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

    /** @var URNPubIdPlugin */
    public $_plugin;

    /**
     * Get the plugin.
     *
     * @return URNPubIdPlugin
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
     * @param URNPubIdPlugin $plugin
     * @param int $contextId
     */
    public function __construct($plugin, $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'urnObjects', 'required', 'plugins.pubIds.urn.manager.settings.urnObjectsRequired', function ($enableIssueURN) use ($form) {
            return $form->getData('enableIssueURN') || $form->getData('enablePublicationURN') || $form->getData('enableRepresentationURN');
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'urnPrefix', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPrefixPattern', '/^urn:[a-zA-Z0-9-]*:.*/'));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'urnIssueSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnIssueSuffixPatternRequired', function ($urnIssueSuffixPattern) use ($form) {
            if ($form->getData('urnSuffix') == 'pattern' && $form->getData('enableIssueURN')) {
                return $urnIssueSuffixPattern != '';
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'urnPublicationSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPublicationSuffixPatternRequired', function ($urnPublicationSuffixPattern) use ($form) {
            if ($form->getData('urnSuffix') == 'pattern' && $form->getData('enablePublicationURN')) {
                return $urnPublicationSuffixPattern != '';
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'urnRepresentationSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnRepresentationSuffixPatternRequired', function ($urnRepresentationSuffixPattern) use ($form) {
            if ($form->getData('urnSuffix') == 'pattern' && $form->getData('enableRepresentationURN')) {
                return $urnRepresentationSuffixPattern != '';
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorUrl($this, 'urnResolver', 'required', 'plugins.pubIds.urn.manager.settings.form.urnResolverRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));

        // for URN reset requests
        $request = Application::get()->getRequest();
        $this->setData('clearPubIdsLinkAction', new LinkAction(
            'reassignURNs',
            new RemoteActionConfirmationModal(
                $request->getSession(),
                __('plugins.pubIds.urn.manager.settings.urnReassign.confirm'),
                __('common.delete'),
                $request->url(null, null, 'manage', null, ['verb' => 'clearPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds']),
                'modal_delete'
            ),
            __('plugins.pubIds.urn.manager.settings.urnReassign'),
            'delete'
        ));
        $this->setData('pluginName', $plugin->getName());
    }


    //
    // Implement template methods from Form
    //
    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $urnNamespaces = [
            '' => '',
            'urn:nbn:de' => 'urn:nbn:de',
            'urn:nbn:at' => 'urn:nbn:at',
            'urn:nbn:ch' => 'urn:nbn:ch',
            'urn:nbn:fi' => 'urn:nbn:fi',
            'urn:nbn' => 'urn:nbn',
            'urn' => 'urn'
        ];
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('urnNamespaces', $urnNamespaces);
        return parent::fetch($request, $template, $display);
    }

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
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
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
            'enableIssueURN' => 'bool',
            'enablePublicationURN' => 'bool',
            'enableRepresentationURN' => 'bool',
            'urnPrefix' => 'string',
            'urnSuffix' => 'string',
            'urnIssueSuffixPattern' => 'string',
            'urnPublicationSuffixPattern' => 'string',
            'urnRepresentationSuffixPattern' => 'string',
            'urnCheckNo' => 'bool',
            'urnNamespace' => 'string',
            'urnResolver' => 'string',
        ];
    }
}
