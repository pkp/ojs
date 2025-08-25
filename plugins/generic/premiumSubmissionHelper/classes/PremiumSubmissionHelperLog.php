<?php

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\classes;

// PKP classes
use PKP\core\Core;
use PKP\db\DataObject;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;

/**
 * @file classes/PremiumSubmissionHelperLog.inc.php
 *
 * @class PremiumSubmissionHelperLog
 * @ingroup classes_plugins_generic_premiumSubmissionHelper
 *
 * @brief Gestion des journaux d'activité du plugin
 */
class PremiumSubmissionHelperLog extends DataObject
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        // Initialisation des propriétés par défaut
        $this->setData('logId', 0);
        $this->setContextId(0);
        $this->setUserId(null);
        $this->setSubmissionId(null);
        $this->setDateLogged(gmdate('Y-m-d H:i:s'));
        $this->setEventType('');
        $this->setMessage('');
        $this->setData('data', []);
    }

    //
    // Getters et setters
    //

    /**
     * Récupère l'identifiant du log
     * @return int L'identifiant du log
     */
    public function getLogId(): int
    {
        return $this->getData('logId');
    }

    /**
     * Définit l'identifiant du log
     * @param int $logId L'identifiant du log
     * @return void
     */
    public function setLogId(int $logId): void
    {
        $this->setData('logId', $logId);
    }

    /**
     * Récupère l'identifiant du contexte
     * @return int L'identifiant du contexte
     */
    public function getContextId(): int
    {
        return $this->getData('contextId');
    }

    /**
     * Définit l'identifiant du contexte
     * @param int $contextId L'identifiant du contexte
     * @return void
     */
    public function setContextId(int $contextId): void
    {
        $this->setData('contextId', $contextId);
    }

    /**
     * Récupère l'identifiant de l'utilisateur associé au log
     * @return int|null L'identifiant de l'utilisateur ou null si non défini
     */
    public function getUserId(): ?int
    {
        return $this->getData('userId');
    }

    /**
     * Définit l'identifiant de l'utilisateur associé au log
     * @param int|null $userId L'identifiant de l'utilisateur ou null
     * @return void
     */
    public function setUserId(?int $userId): void
    {
        $this->setData('userId', $userId);
    }

    /**
     * Récupère l'identifiant de la soumission associée au log
     * @return int|null L'identifiant de la soumission ou null si non défini
     */
    public function getSubmissionId(): ?int
    {
        return $this->getData('submissionId');
    }

    /**
     * Définit l'identifiant de la soumission associée au log
     * @param int|null $submissionId L'identifiant de la soumission ou null
     * @return void
     */
    public function setSubmissionId(?int $submissionId): void
    {
        $this->setData('submissionId', $submissionId);
    }

    /**
     * Récupère la date et l'heure de la journalisation
     * @return string La date et l'heure au format 'Y-m-d H:i:s'
     */
    public function getDateLogged(): string
    {
        return $this->getData('dateLogged');
    }

    /**
     * Définit la date et l'heure de la journalisation
     * @param string $dateLogged La date et l'heure au format 'Y-m-d H:i:s'
     * @return void
     */
    public function setDateLogged(string $dateLogged): void
    {
        $this->setData('dateLogged', $dateLogged);
    }

    /**
     * Récupère le type d'événement du log
     * @return string Le type d'événement
     */
    public function getEventType(): string
    {
        return $this->getData('eventType');
    }

    /**
     * Définit le type d'événement du log
     * @param string $eventType Le type d'événement
     * @return void
     */
    public function setEventType(string $eventType): void
    {
        $this->setData('eventType', $eventType);
    }

    /**
     * Récupère l'adresse IP associée au log
     * @return string L'adresse IP
     */
    public function getIpAddress(): string
    {
        return $this->getData('ipAddress');
    }

    /**
     * Définit l'adresse IP associée au log
     * @param string $ipAddress L'adresse IP
     * @return void
     */
    public function setIpAddress(string $ipAddress): void
    {
        $this->setData('ipAddress', $ipAddress);
    }

    /**
     * Récupère le message du log
     * @return string Le message du log
     */
    public function getMessage(): string
    {
        return $this->getData('message');
    }

    /**
     * Définit le message du log
     * @param string $message Le message du log
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->setData('message', $message);
    }

    /**
     * Récupère les paramètres supplémentaires du log
     * @return array Les paramètres du log
     */
    public function getParams(): array
    {
        return $this->getData('params');
    }

    /**
     * Définit les paramètres supplémentaires du log
     * @param array $params Les paramètres du log
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->setData('params', $params);
    }

    /**
     * Journalise un événement dans le système de logs
     *
     * @param int $contextId ID du contexte (journal)
     * @param int|null $userId ID de l'utilisateur à l'origine de l'événement (optionnel)
     * @param int|null $submissionId ID de la soumission concernée (optionnel)
     * @param string $eventType Type d'événement (constante définissant la catégorie)
     * @param string $message Message descriptif de l'événement
     * @param array $data Données supplémentaires au format tableau (optionnel)
     * @return bool True si l'enregistrement a réussi, false sinon
     */
    public static function logEvent(int $contextId, ?int $userId, ?int $submissionId, string $eventType, string $message, array $data = []): bool
    {
        $log = new self();
        $log->setContextId($contextId);
        $log->setUserId($userId);
        $log->setSubmissionId($submissionId);
        $log->setDateLogged(Core::getCurrentDate());
        $log->setEventType($eventType);
        $log->setMessage($message);
        $log->setData($data);

        /** @var PremiumSubmissionHelperLogDAO $logDao */
        $logDao = DAORegistry::getDAO('PremiumHelperLogDAO');
        return (bool) $logDao->insertObject($log);
    }

    /**
     * Purge les anciennes entrées de journal selon la rétention configurée
     *
     * @param int $daysToKeep Nombre de jours de rétention (défaut: 90 jours)
     * @return int Nombre d'entrées supprimées
     */
    public static function purgeOldLogs(int $daysToKeep = 90): int
    {
        /** @var PremiumSubmissionHelperLogDAO $logDao */
        $logDao = DAORegistry::getDAO('PremiumHelperLogDAO');
        $cutoffTimestamp = time() - ($daysToKeep * 24 * 60 * 60);
        return $logDao->deleteByDateBefore($cutoffTimestamp);
    }
}
