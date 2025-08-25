<?php

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\classes;

// PKP classes
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\db\DBResultRange;
use PKP\db\SchemaDAO;
use PKP\plugins\PluginRegistry;

// Plugin classes
use APP\plugins\generic\premiumSubmissionHelper\PremiumSubmissionHelperLog;

/**
 * @file classes/PremiumSubmissionHelperLogDAO.inc.php
 *
 * @class PremiumSubmissionHelperLogDAO
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Opérations de base de données pour les journaux du plugin
 */
class PremiumSubmissionHelperLogDAO extends DAO
{
    /**
     * @copydoc SchemaDAO::getCacheName()
     * @return string Le nom du cache pour cette entité
     */
    protected function getCacheName(): string
    {
        return 'premiumSubmissionHelperLogs';
    }

    /**
     * @copydoc DAO::_getTableName()
     * @return string Le nom de la table de la base de données
     */
    protected function getTableName(): string
    {
        return 'premiumsubmissionhelper_logs';
    }

    /**
     * @copydoc DAO::_getPrimaryKeyColumn()
     * @return string Le nom de la colonne de clé primaire
     */
    protected function getPrimaryKeyColumn(): string
    {
        return 'log_id';
    }

    /**
     * @copydoc DAO::_fromRow()
     * @param array|object $row Ligne de base de données
     * @return PremiumSubmissionHelperLog
     */
    protected function _fromRow($row): PremiumSubmissionHelperLog
    {
        $log = new PremiumSubmissionHelperLog();
        $log->setLogId($row->log_id);
        $log->setContextId($row->context_id);
        $log->setUserId($row->user_id);
        $log->setSubmissionId($row->submission_id);
        $log->setDateLogged($row->date_logged);
        $log->setEventType($row->event_type);
        $log->setMessage($row->message);
        $log->setData($row->data);

        return $log;
    }

    /**
     * @copydoc DAO::_toRow()
     * @param PremiumSubmissionHelperLog $log L'objet log à convertir en tableau
     * @return array Tableau associatif des données du log
     */
    protected function _toRow(PremiumSubmissionHelperLog $log): array
    {
        return [
            'context_id' => $log->getContextId(),
            'user_id' => $log->getUserId(),
            'submission_id' => $log->getSubmissionId(),
            'date_logged' => $log->getDateLogged(),
            'event_type' => $log->getEventType(),
            'message' => $log->getMessage(),
            'data' => json_encode($log->getData())
        ];
    }

    /**
     * Insère un nouvel objet log dans la base de données
     * @param PremiumSubmissionHelperLog $log L'objet log à insérer
     * @return int L'ID du log inséré
     */
    public function insertObject(PremiumSubmissionHelperLog $log): int
    {
        $this->update(
            'INSERT INTO ' . $this->_getTableName() . '
                (context_id, user_id, submission_id, date_logged, event_type, message, data)
                VALUES
                (?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $log->getContextId(),
                $log->getUserId() ? (int) $log->getUserId() : null,
                $log->getSubmissionId() ? (int) $log->getSubmissionId() : null,
                $log->getDateLogged(),
                $log->getEventType(),
                $log->getMessage(),
                json_encode($log->getData())
            ]
        );

        $log->setLogId($this->_getInsertId());
        return $log->getLogId();
    }

    /**
     * @copydoc DAO::newDataObject()
     * @return PremiumSubmissionHelperLog
     */
    public function newDataObject(): PremiumSubmissionHelperLog
    {
        return new PremiumSubmissionHelperLog();
    }

    /**
     * Met à jour un objet log dans la base de données
     * @param PremiumSubmissionHelperLog $log L'objet log à mettre à jour
     * @return bool
     */
    public function updateObject(PremiumSubmissionHelperLog $log): bool
    {
        return (bool) $this->update(
            'UPDATE ' . $this->_getTableName() . ' SET
                context_id = ?,
                user_id = ?,
                submission_id = ?,
                date_logged = ?,
                event_type = ?,
                message = ?,
                data = ?
            WHERE log_id = ?',
            [
                (int) $log->getContextId(),
                $log->getUserId() ? (int) $log->getUserId() : null,
                $log->getSubmissionId() ? (int) $log->getSubmissionId() : null,
                $log->getDateLogged(),
                $log->getEventType(),
                $log->getMessage(),
                json_encode($log->getData()),
                (int) $log->getLogId()
            ]
        );
    }

    /**
     * Supprime un log par son ID
     * @param int $logId L'ID du log à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deleteById(int $logId): bool
    {
        return (bool) $this->update(
            'DELETE FROM ' . $this->_getTableName() . ' WHERE log_id = ?',
            [(int) $logId]
        );
    }

    /**
     * Supprime les journaux antérieurs à une date donnée
     *
     * @param int $timestamp Timestamp Unix
     * @return int Nombre de lignes supprimées
     */
    public function deleteByDateBefore(int $timestamp): int
    {
        return $this->update(
            'DELETE FROM ' . $this->_getTableName() . ' WHERE date_logged < ?',
            [date('Y-m-d H:i:s', $timestamp)]
        );
    }

    /**
     * Récupère un journal par son ID
     *
     * @param int $logId
     * @return PremiumHelperLog|null
     */
    /**
     * Récupère un log par son ID
     * @param int $logId L'ID du log à récupérer
     * @return ?PremiumSubmissionHelperLog
     */
    public function getById(int $logId): ?PremiumSubmissionHelperLog
    {
        $result = $this->retrieve(
            'SELECT * FROM ' . $this->_getTableName() . ' WHERE log_id = ?',
            [(int) $logId]
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Récupère les logs par ID de contexte
     * @param int $contextId L'ID du contexte
     * @param ?DBResultRange $rangeInfo Informations de pagination (optionnel)
     * @return DAOResultFactory<PremiumSubmissionHelperLog> Itérateur de résultats
     */
    public function getByContextId(int $contextId, ?DBResultRange $rangeInfo = null): DAOResultFactory
    {
        $params = [$contextId];
        $sql = 'SELECT * FROM ' . $this->_getTableName() . ' WHERE context_id = ?';
        
        if ($rangeInfo) {
            $result = $this->retrieveRange($sql, $params, $rangeInfo);
        } else {
            $result = $this->retrieve($sql, $params);
        }
        
        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Compte le nombre de journaux correspondant aux critères de recherche
     *
     * @param array{
     *     contextId?: int,
     *     userId?: int,
     *     submissionId?: int,
     *     eventType?: string,
     *     dateFrom?: string,
     *     dateTo?: string
     * } $filters Filtres de recherche
     * @return int
     */
    public function countByFilters(array $filters = []): int
    {
        $params = [];
        $where = [];

        if (!empty($filters['contextId'])) {
            $where[] = 'context_id = ?';
            $params[] = (int) $filters['contextId'];
        }

        if (!empty($filters['userId'])) {
            $where[] = 'user_id = ?';
            $params[] = (int) $filters['userId'];
        }

        if (!empty($filters['submissionId'])) {
            $where[] = 'submission_id = ?';
            $params[] = (int) $filters['submissionId'];
        }

        if (!empty($filters['eventType'])) {
            $where[] = 'event_type = ?';
            $params[] = $filters['eventType'];
        }

        if (!empty($filters['dateFrom'])) {
            $where[] = 'date_logged >= ?';
            $params[] = date('Y-m-d H:i:s', strtotime($filters['dateFrom']));
        }

        if (!empty($filters['dateTo'])) {
            $where[] = 'date_logged <= ?';
            $params[] = date('Y-m-d H:i:s', strtotime($filters['dateTo'] . ' 23:59:59'));
        }

        $sql = 'SELECT COUNT(*) FROM ' . $this->_getTableName();
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return $this->getOne($sql, $params);
    }
}
