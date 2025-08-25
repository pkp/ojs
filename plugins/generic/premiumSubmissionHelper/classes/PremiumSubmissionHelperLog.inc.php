<?php

namespace APP\plugins\generic\premiumSubmissionHelper\classes;

use PKP\db\DataObject;

/**
 * @file classes/PremiumSubmissionHelperLog.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperLog
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Gestion des journaux d'activité du plugin
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.premiumSubmissionHelper.PremiumSubmissionHelperPlugin');

class PremiumSubmissionHelperLog extends DataObject
{
    /**
     * Constructeur
     */
    public function __construct()
    {
        parent::__construct();
    }

    //
    // Getters et setters
    //

    /**
     * Obtient l'ID du journal
     * @return int
     */
    public function getLogId()
    {
        return $this->getData('logId');
    }

    /**
     * Définit l'ID du journal
     * @param int $logId
     */
    public function setLogId($logId)
    {
        $this->setData('logId', $logId);
    }

    /**
     * Obtient l'ID du contexte
     * @return int
     */
    public function getContextId()
    {
        return $this->getData('contextId');
    }

    /**
     * Définit l'ID du contexte
     * @param int $contextId
     */
    public function setContextId($contextId)
    {
        $this->setData('contextId', $contextId);
    }

    /**
     * Obtient l'ID de l'utilisateur
     * @return int|null
     */
    public function getUserId()
    {
        return $this->getData('userId');
    }

    /**
     * Définit l'ID de l'utilisateur
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->setData('userId', $userId);
    }

    /**
     * Obtient l'ID de la soumission
     * @return int|null
     */
    public function getSubmissionId()
    {
        return $this->getData('submissionId');
    }

    /**
     * Définit l'ID de la soumission
     * @param int $submissionId
     */
    public function setSubmissionId($submissionId)
    {
        $this->setData('submissionId', $submissionId);
    }

    /**
     * Obtient la date de journalisation
     * @return string
     */
    public function getDateLogged()
    {
        return $this->getData('dateLogged');
    }

    /**
     * Définit la date de journalisation
     * @param string $dateLogged
     */
    public function setDateLogged($dateLogged)
    {
        $this->setData('dateLogged', $dateLogged);
    }

    /**
     * Obtient le type d'événement
     * @return string
     */
    public function getEventType()
    {
        return $this->getData('eventType');
    }

    /**
     * Définit le type d'événement
     * @param string $eventType
     */
    public function setEventType($eventType)
    {
        $this->setData('eventType', $eventType);
    }

    /**
     * Obtient le message du journal
     * @return string
     */
    public function getMessage()
    {
        return $this->getData('message');
    }

    /**
     * Définit le message du journal
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->setData('message', $message);
    }

    /**
     * Obtient les données supplémentaires
     * @return array
     */
    public function getData()
    {
        return $this->getData('data') ? json_decode($this->getData('data'), true) : [];
    }

    /**
     * Définit les données supplémentaires
     * @param array $data
     */
    public function setData($data)
    {
        $this->setData('data', json_encode($data));
    }

    /**
     * Journalise un événement
     *
     * @param int $contextId ID du contexte
     * @param int|null $userId ID de l'utilisateur (optionnel)
     * @param int|null $submissionId ID de la soumission (optionnel)
     * @param string $eventType Type d'événement
     * @param string $message Message de journalisation
     * @param array $data Données supplémentaires (optionnel)
     * @return bool
     */
    public static function logEvent($contextId, $userId, $submissionId, $eventType, $message, $data = [])
    {
        $log = new self();
        $log->setContextId($contextId);
        $log->setUserId($userId);
        $log->setSubmissionId($submissionId);
        $log->setDateLogged(Core::getCurrentDate());
        $log->setEventType($eventType);
        $log->setMessage($message);
        $log->setData($data);

        $logDao = DAORegistry::getDAO('PremiumHelperLogDAO');
        return (bool) $logDao->insertObject($log);
    }

    /**
     * Purge les anciennes entrées de journal
     *
     * @param int $daysToKeep Nombre de jours à conserver (par défaut: 90)
     * @return int Nombre d'entrées supprimées
     */
    public static function purgeOldLogs($daysToKeep = 90)
    {
        $logDao = DAORegistry::getDAO('PremiumHelperLogDAO');
        return $logDao->deleteByDateBefore(time() - ($daysToKeep * 24 * 60 * 60));
    }
}
