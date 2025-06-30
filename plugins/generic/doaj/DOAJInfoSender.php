<?php

/**
 * @file plugins/generic/doaj/DOAJInfoSender.php
 *
 * Copyright (c) 2013-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJInfoSender
 *
 * @brief Scheduled task to send deposits to DOAJ.
 */

namespace APP\plugins\generic\doaj;

use APP\core\Application;
use APP\journal\Journal;
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
    public function __construct(array $args = [])
    {
        PluginRegistry::loadCategory('importexport');

        $plugin = PluginRegistry::getPlugin('importexport', 'DOAJExportPlugin'); /** @var DOAJExportPlugin $plugin */
        $this->_plugin = $plugin;

        if ($plugin instanceof DOAJExportPlugin) {
            $plugin->addLocaleData();
        }

        parent::__construct($args);
    }

    /**
     * @copydoc ScheduledTask::getName()
     */
    public function getName(): string
    {
        return __('plugins.importexport.doaj.senderTask.name');
    }

    /**
     * @copydoc ScheduledTask::executeActions()
     */
    public function executeActions(): bool
    {
        if (!$this->_plugin) {
            return false;
        }

        $plugin = $this->_plugin;
        $journals = $this->_getJournals();

        foreach ($journals as $journal) {
            // load pubIds for this journal
            PluginRegistry::loadCategory('pubIds', true, $journal->getId());
            if ($journal->getData(Journal::SETTING_DOI_VERSIONING)) {
                $depositablePublications = $plugin->getAllDepositablePublications($journal);
                if (count($depositablePublications)) {
                    $this->_registerObjects($depositablePublications, 'publication=>doaj-json', $journal);
                }
            } else {
                $depositableArticles = $plugin->getAllDepositableArticles($journal);
                if (count($depositableArticles)) {
                    $this->_registerObjects($depositableArticles, 'article=>doaj-json', $journal);
                }
            }
        }

        return true;
    }

    /**
     * Get all journals that meet the requirements to have
     * their articles automatically sent to DOAJ.
     *
     * @return array<Journal>
     */
    protected function _getJournals(): array
    {
        $plugin = $this->_plugin;
        $contextDao = Application::getContextDAO(); /** @var JournalDAO $contextDao */
        $journalFactory = $contextDao->getAll(true);

        $journals = [];
        while ($journal = $journalFactory->next()) { /** @var  Journal $journal */
            $journalId = $journal->getId();
            if (!$plugin->getSetting($journalId, 'apiKey') || !$plugin->getSetting($journalId, 'automaticRegistration')) {
                continue;
            }
            $journals[] = $journal;
        }
        return $journals;
    }

    /**
     * Register articles or publications
     *
     * @param array<Article|Publication> $objects
     */
    protected function _registerObjects(array $objects, string $filter, Journal $journal): void
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
     */
    protected function _addLogEntry(array $errors): void
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
