<?php

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\scheduledTasks;

// Application classes
use APP\facades\Repo;
use APP\plugins\generic\premiumSubmissionHelper\PremiumSubmissionHelperPlugin;

// PKP classes
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

/**
 * @file scheduledTasks/PremiumSubmissionHelperScheduledTask.inc.php
 *
 * @class PremiumSubmissionHelperScheduledTask
 * @ingroup scheduled_tasks
 *
 * @brief Tâche planifiée pour le plugin Premium Submission Helper
 */
class PremiumSubmissionHelperScheduledTask extends ScheduledTask
{
    protected PremiumSubmissionHelperPlugin $plugin;

    /**
     * Constructor
     * @param array $args Arguments
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
    public function executeActions()
    {
        if (!$this->plugin) {
            return false;
        }

        $this->cleanupOldLogs();

        return true;
    }

    /**
     * Nettoie les anciens logs selon la configuration de rétention
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
