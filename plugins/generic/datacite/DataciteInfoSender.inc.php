<?php

/**
 * @file plugins/generic/datacite/DataciteInfoSender.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteInfoSender
 * @ingroup plugins_generic_datacite
 *
 * @brief Scheduled task to send deposits to DataCite.
 */

use APP\facades\Repo;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class DataciteInfoSender extends ScheduledTask
{
    /** @var DataciteExportPlugin $_plugin */
    public $_plugin;

    /**
     * Constructor.
     */
    public function __construct($args)
    {
        PluginRegistry::loadCategory('importexport');
        $plugin = PluginRegistry::getPlugin('importexport', 'DataciteExportPlugin'); /* @var $plugin DataciteExportPlugin */
        $this->_plugin = $plugin;

        if (is_a($plugin, 'DataciteExportPlugin')) {
            $plugin->addLocaleData();
        }

        parent::__construct($args);
    }

    /**
     * @copydoc ScheduledTask::getName()
     */
    public function getName()
    {
        return __('plugins.importexport.datacite.senderTask.name');
    }

    /**
     * @copydoc ScheduledTask::executeActions()
     */
    public function executeActions()
    {
        if (!$this->_plugin) {
            return false;
        }

        $contexts = $this->_getJournals();

        foreach ($contexts as $context) {
            Repo::doi()->scheduleDepositAll($context);
        }
        return true;
    }

    /**
     * Get all journals that meet the requirements to have
     * their DOIs sent to DataCite.
     *
     * @return array
     */
    public function _getJournals()
    {
        $plugin = $this->_plugin;
        $contextDao = Application::getContextDAO(); /* @var $contextDao JournalDAO */
        $journalIds = Services::get('context')->getIds(['isEnabled' => true]);

        $journals = [];
        foreach ($journalIds as $journalId) {
            if (!$plugin->getSetting($journalId, 'username') || !$plugin->getSetting($journalId, 'password') || !$plugin->getSetting($journalId, 'automaticRegistration')) {
                continue;
            }

            $journal = $contextDao->getById($journalId);
            if (!$journal->getData(Context::SETTING_ENABLE_DOIS)) {
                continue;
            }

            $doiPrefix = $journal->getData(Context::SETTING_DOI_PREFIX);
            if ($doiPrefix) {
                $journals[] = $journal;
            } else {
                $this->addExecutionLogEntry(__('plugins.importexport.common.senderTask.warning.noDOIprefix', ['path' => $journal->getPath()]), ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
            }
        }
        return $journals;
    }
}
