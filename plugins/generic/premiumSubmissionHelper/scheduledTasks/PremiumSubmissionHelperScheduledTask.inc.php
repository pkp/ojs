<?php

/**
 * @file scheduledTasks/PremiumSubmissionHelperScheduledTask.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperScheduledTask
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Tâche planifiée pour le plugin Premium Submission Helper
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');

class PremiumSubmissionHelperScheduledTask extends ScheduledTask
{
    /** @var PremiumSubmissionHelperPlugin Le plugin */
    protected $plugin;

    /**
     * Constructeur
     *
     * @param array $args Arguments de la tâche
     */
    public function __construct($args)
    {
        $this->addSupportedScheduledTask(0, 'premiumSubmissionHelperTask');
        parent::__construct($args);

        // Charger le plugin
        $plugin = PluginRegistry::getPlugin('generic', 'premiumsubmissionhelperplugin');
        if ($plugin instanceof PremiumSubmissionHelperPlugin) {
            $this->plugin = $plugin;
        } else {
            $this->addExecutionLogEntry(
                'Impossible de charger le plugin Premium Submission Helper',
                SCHEDULED_TASK_MESSAGE_TYPE_ERROR
            );
            $this->handleError();
        }
    }

    /**
     * @copydoc ScheduledTask::executeActions()
     */
    protected function executeActions()
    {
        if (!$this->plugin) {
            return false;
        }

        $this->cleanupOldLogs();

        return true;
    }

    /**
     * Nettoie les anciennes entrées de journal
     */
    protected function cleanupOldLogs()
    {
        $plugin = $this->plugin;
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAll();
        $daysToKeep = 90; // Par défaut, conserver les journaux pendant 90 jours

        while ($context = $contexts->next()) {
            $contextId = $context->getId();

            // Vérifier si le plugin est activé pour ce contexte
            if (!$plugin->getSetting($contextId, 'enabled')) {
                continue;
            }

            // Récupérer les paramètres du plugin
            $settings = $plugin->getSetting($contextId, 'settings');

            // Utiliser la période de rétention personnalisée si définie
            if (isset($settings['logRetentionDays']) && $settings['logRetentionDays'] > 0) {
                $daysToKeep = (int) $settings['logRetentionDays'];
            }

            // Calculer la date de coupure
            $cutoffDate = strtotime("-$daysToKeep days");

            // Nettoyer les journaux
            $logDao = DAORegistry::getDAO('PremiumHelperLogDAO');
            $deleted = $logDao->deleteByDateBefore($cutoffDate);

            // Enregistrer un message de journal
            $this->addExecutionLogEntry(
                __(
                    'plugins.generic.premiumHelper.log.cleanup',
                    [
                        'contextName' => $context->getLocalizedName(),
                        'count' => $deleted,
                        'days' => $daysToKeep
                    ]
                ),
                SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
            );
        }
    }
}
