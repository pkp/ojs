<?php
/**
 * @file classes/PremiumSubmissionHelperLogDAO.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperLogDAO
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Opérations de base de données pour les journaux du plugin
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.premiumSubmissionHelper.classes.PremiumSubmissionHelperLog');

class PremiumSubmissionHelperLogDAO extends DAO {
    /**
     * @copydoc DAO::_getCacheName()
     */
    protected function _getCacheName() {
        return 'premiumSubmissionHelperLogs';
    }
    
    /**
     * @copydoc DAO::_getTableName()
     */
    protected function _getTableName() {
        return 'premiumsubmissionhelper_logs';
    }
    
    /**
     * @copydoc DAO::_getPrimaryKeyColumn()
     */
    protected function _getPrimaryKeyColumn() {
        return 'log_id';
    }
    
    /**
     * @copydoc DAO::_fromRow()
     */
    protected function _fromRow($row) {
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
     */
    protected function _toRow($log) {
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
     * Crée un nouvel enregistrement de journal
     * @param PremiumSubmissionHelperLog $log L'objet journal à enregistrer
     * @return int L'ID du journal créé
     */
    public function insertObject($log) {
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
     * Met à jour un objet de journal existant
     * 
     * @param PremiumHelperLog $log
     * @return bool
     */
    public function updateObject($log) {
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
     * Supprime un journal par son ID
     * 
     * @param int $logId
     * @return bool
     */
    public function deleteById($logId) {
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
    public function deleteByDateBefore($timestamp) {
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
    public function getById($logId) {
        $result = $this->retrieve(
            'SELECT * FROM ' . $this->_getTableName() . ' WHERE log_id = ?',
            [(int) $logId]
        );
        
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }
    
    /**
     * Récupère les journaux correspondant aux critères de recherche
     * 
     * @param array $filters Filtres de recherche (contextId, userId, submissionId, eventType, dateFrom, dateTo)
     * @param DBResultRange $rangeInfo Plage de résultats (optionnel)
     * @return DAOResultFactory
     */
    public function getByFilters($filters = [], $rangeInfo = null) {
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
        
        $sql = 'SELECT * FROM ' . $this->_getTableName();
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY date_logged DESC';
        
        return new DAOResultFactory(
            $this->retrieveRange($sql, $params, $rangeInfo),
            $this,
            '_fromRow',
            [],
            $sql,
            $params,
            $rangeInfo
        );
    }
    
    /**
     * Compte le nombre de journaux correspondant aux critères de recherche
     * 
     * @param array $filters Filtres de recherche
     * @return int
     */
    public function countByFilters($filters = []) {
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
