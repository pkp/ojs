<?php

/**
 * @file plugins/importexport/doaj/DOAJInfoSender.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJInfoSender
 *
 * @brief Scheduled task to send deposits to DOAJ.
 */

namespace APP\plugins\importexport\doaj;

use APP\core\Application;
use APP\journal\JournalDAO;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class DOAJInfoSender extends ScheduledTask
{
    /** @var DOAJExportPlugin $_plugin */
    public $_plugin;

    /**
     * Constructor.
     */
    public function __construct($args)
    {
        PluginRegistry::loadCategory('importexport');
        $plugin = PluginRegistry::getPlugin('importexport', 'DOAJExportPlugin'); /** @var DOAJExportPlugin $plugin */
        $this->_plugin = $plugin;

        if (is_a($plugin, 'DOAJExportPlugin')) {
            $plugin->addLocaleData();
        }

        parent::__construct($args);
    }

    /**
     * @copydoc ScheduledTask::getName()
     */
    public function getName()
    {
        return __('plugins.importexport.doaj.senderTask.name');
    }

    /**
     * @copydoc ScheduledTask::executeActions()
     */
    public function executeActions()
    {
        if (!$this->_plugin) {
            return false;
        }

        $plugin = $this->_plugin;
        $journals = $this->_getJournals();

        foreach ($journals as $journal) {
            // load pubIds for this journal
            PluginRegistry::loadCategory('pubIds', true, $journal->getId());
            // Get unregistered articles
            $unregisteredArticles = $plugin->getUnregisteredArticles($journal);
            // If there are articles to be deposited
            if (count($unregisteredArticles)) {
                $this->_registerObjects($unregisteredArticles, 'article=>doaj-json', $journal, 'articles');
            }
        }
        return true;
    }

    /**
     * Get all journals that meet the requirements to have
     * their articles automatically sent to DOAJ.
     *
     * @return array
     */
    public function _getJournals()
    {
        $plugin = $this->_plugin;
        $contextDao = Application::getContextDAO(); /** @var JournalDAO $contextDao */
        $journalFactory = $contextDao->getAll(true);

        $journals = [];
        while ($journal = $journalFactory->next()) {
            $journalId = $journal->getId();
            if (!$plugin->getSetting($journalId, 'apiKey') || !$plugin->getSetting($journalId, 'automaticRegistration')) {
                continue;
            }
            $journals[] = $journal;
        }
        return $journals;
    }


    /**
     * Register objects
     *
     * @param array $objects
     * @param string $filter
     * @param \APP\journal\Journal $journal
     * @param string $objectsFileNamePart
     */
    public function _registerObjects($objects, $filter, $journal, $objectsFileNamePart)
    {
        $plugin = $this->_plugin;
        foreach ($objects as $object) {
            // Get the JSON
            $exportJson = $plugin->exportJSON($object, $filter, $journal);
            // Deposit the JSON
            $result = $plugin->depositXML($object, $journal, $exportJson);
            if ($result !== true) {
                $this->_addLogEntry($result);
            }
        }
    }

    /**
     * Add execution log entry
     *
     * @param array $errors
     */
    public function _addLogEntry($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $error) {
                assert(is_array($error) && count($error) >= 1);
                $this->addExecutionLogEntry(
                    __($error[0], ['param' => $error[1] ?? null]),
                    ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_WARNING
                );
            }
        }
    }
}
