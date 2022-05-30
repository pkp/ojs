<?php

/**
 * @file plugins/generic/webFeed/WebFeedSettingsForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedSettingsForm
 * @ingroup plugins_generic_webFeed
 *
 * @brief Form for managers to modify web feeds plugin settings
 */

use APP\template\TemplateManager;

use PKP\form\Form;

class WebFeedSettingsForm extends Form
{
    /** @var int Associated context ID */
    private $_contextId;

    /** @var WebFeedPlugin Web feed plugin */
    private $_plugin;

    /**
     * Constructor
     *
     * @param WebFeedPlugin $plugin Web feed plugin
     * @param int $contextId Context ID
     */
    public function __construct($plugin, $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
        $contextId = $this->_contextId;
        $plugin = $this->_plugin;

        $this->setData('displayPage', $plugin->getSetting($contextId, 'displayPage'));
        $this->setData('displayItems', $plugin->getSetting($contextId, 'displayItems'));
        $this->setData('recentItems', $plugin->getSetting($contextId, 'recentItems'));
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(['displayPage','displayItems','recentItems']);

        // check that recent items value is a positive integer
        if ((int) $this->getData('recentItems') <= 0) {
            $this->setData('recentItems', '');
        }

        // if recent items is selected, check that we have a value
        if ($this->getData('displayItems') == 'recent') {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'recentItems', 'required', 'plugins.generic.webfeed.settings.recentItemsRequired'));
        }
    }

    /**
     * Fetch the form.
     *
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->_plugin;
        $contextId = $this->_contextId;

        $plugin->updateSetting($contextId, 'displayPage', $this->getData('displayPage'));
        $plugin->updateSetting($contextId, 'displayItems', $this->getData('displayItems'));
        $plugin->updateSetting($contextId, 'recentItems', $this->getData('recentItems'));

        parent::execute(...$functionArgs);
    }
}
