<?php

/**
 * @file classes/payment/ojs/OJSCompletedPaymentDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSCompletedPaymentDAO
 *
 * @ingroup payment
 *
 * @see OJSCompletedPayment, Payment
 *
 * @brief Operations for retrieving and querying past payments
 *
 */

namespace APP\payment\ojs;

use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\db\DAOResultFactory;
use PKP\db\DBResultRange;
use PKP\payment\CompletedPayment;

class OJSCompletedPaymentDAO extends \PKP\db\DAO
{
    /**
     * Retrieve a CompletedPayment by its ID.
     *
     * @param int $completedPaymentId
     * @param int $contextId optional
     *
     * @return CompletedPayment
     */
    public function getById($completedPaymentId, $contextId = null)
    {
        $params = [(int) $completedPaymentId];
        if ($contextId) {
            $params[] = (int) $contextId;
        }

        $result = $this->retrieve(
            'SELECT * FROM completed_payments WHERE completed_payment_id = ?' . ($contextId ? ' AND context_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Insert a new completed payment.
     *
     * @param CompletedPayment $completedPayment
     */
    public function insertObject($completedPayment)
    {
        $this->update(
            sprintf(
                'INSERT INTO completed_payments
				(timestamp, payment_type, context_id, user_id, assoc_id, amount, currency_code_alpha, payment_method_plugin_name)
				VALUES
				(%s, ?, ?, ?, ?, ?, ?, ?)',
                $this->datetimeToDB(Core::getCurrentDate())
            ),
            [
                (int) $completedPayment->getType(),
                (int) $completedPayment->getContextId(),
                (int) $completedPayment->getUserId(),
                (int) $completedPayment->getAssocId(),
                $completedPayment->getAmount(),
                $completedPayment->getCurrencyCode(),
                $completedPayment->getPayMethodPluginName()
            ]
        );

        return $this->getInsertId();
    }

    /**
     * Update an existing completed payment.
     *
     * @param CompletedPayment $completedPayment
     */
    public function updateObject($completedPayment)
    {
        $this->update(
            sprintf(
                'UPDATE completed_payments
			SET
				timestamp = %s,
				payment_type = ?,
				context_id = ?,
				user_id = ?,
				assoc_id = ?,
				amount = ?,
				currency_code_alpha = ?,
				payment_method_plugin_name = ?
			WHERE completed_payment_id = ?',
                $this->datetimeToDB($completedPayment->getTimestamp())
            ),
            [
                (int) $completedPayment->getType(),
                (int) $completedPayment->getContextId(),
                (int) $completedPayment->getUserId(),
                (int) $completedPayment->getAssocId(),
                $completedPayment->getAmount(),
                $completedPayment->getCurrencyCode(),
                $completedPayment->getPayMethodPluginName(),
                (int) $completedPayment->getId()
            ]
        );
    }

    /**
     * Delete a completed payment.
     *
     * @param int $completedPaymentId
     */
    public function deleteById($completedPaymentId)
    {
        DB::table('completed_payments')
            ->where('completed_payment_id', '=', $completedPaymentId)
            ->delete();
    }

    /**
     * Get a payment by assoc info
     *
     * @param int? $userId
     * @param int $paymentType PAYMENT_TYPE_...
     * @param int $assocId
     *
     * @return CompletedPayment|null
     */
    public function getByAssoc($userId = null, $paymentType = null, $assocId = null)
    {
        $params = [];
        if ($userId) {
            $params[] = (int) $userId;
        }
        if ($paymentType) {
            $params[] = (int) $paymentType;
        }
        if ($assocId) {
            $params[] = (int) $assocId;
        }
        $result = $this->retrieve(
            'SELECT * FROM completed_payments WHERE 1=1' .
            ($userId ? ' AND user_id = ?' : '') .
            ($paymentType ? ' AND payment_type = ?' : '') .
            ($assocId ? ' AND assoc_id = ?' : ''),
            $params
        );
        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Look for a completed OJSPaymentManager::PAYMENT_TYPE_PURCHASE_ARTICLE payment matching the article ID
     *
     * @param int? $userId
     * @param int $articleId
     */
    public function hasPaidPurchaseArticle($userId, $articleId)
    {
        return $userId && $this->getByAssoc($userId, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_ARTICLE, $articleId);
    }

    /**
     * Look for a completed PAYMENT_TYPE_PURCHASE_ISSUE payment matching the user and issue IDs
     *
     * @param int? $userId
     * @param int $issueId
     */
    public function hasPaidPurchaseIssue($userId, $issueId)
    {
        return $userId && $this->getByAssoc($userId, OJSPaymentManager::PAYMENT_TYPE_PURCHASE_ISSUE, $issueId);
    }

    /**
     * Look for a completed OJSPaymentManager::PAYMENT_TYPE_PUBLICATION payment matching the user and article IDs
     *
     * @param int $userId
     * @param int $articleId
     */
    public function hasPaidPublication($userId, $articleId)
    {
        return $userId && $this->getByAssoc($userId, OJSPaymentManager::PAYMENT_TYPE_PUBLICATION, $articleId);
    }

    /**
     * Retrieve an array of payments for a particular context ID.
     *
     * @param int $contextId
     * @param ?DBResultRange $rangeInfo
     *
     * @return array Matching payments
     */
    public function getByContextId($contextId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT * FROM completed_payments WHERE context_id = ? ORDER BY timestamp DESC',
            [(int) $contextId],
            $rangeInfo
        );

        $returner = [];
        foreach ($result as $row) {
            $payment = $this->_fromRow((array) $row);
            $returner[$payment->getId()] = $payment;
        }
        return $returner;
    }

    /**
     * Retrieve CompletedPayments by user ID
     *
     * @param int $userId User ID
     * @param ?DBResultRange $rangeInfo Optional
     *
     * @return DAOResultFactory<CompletedPayment> Object containing matching CompletedPayment objects
     */
    public function getByUserId($userId, $rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT * FROM completed_payments WHERE user_id = ? ORDER BY timestamp DESC',
            [(int) $userId],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Return a new data object.
     *
     * @return CompletedPayment
     */
    public function newDataObject()
    {
        return new CompletedPayment();
    }

    /**
     * Internal function to return a CompletedPayment object from a row.
     *
     * @param array $row
     *
     * @return CompletedPayment
     */
    public function _fromRow($row)
    {
        $payment = $this->newDataObject();
        $payment->setTimestamp($this->datetimeFromDB($row['timestamp']));
        $payment->setId($row['completed_payment_id']);
        $payment->setType($row['payment_type']);
        $payment->setContextId($row['context_id']);
        $payment->setAmount($row['amount']);
        $payment->setCurrencyCode($row['currency_code_alpha']);
        $payment->setUserId($row['user_id']);
        $payment->setAssocId($row['assoc_id']);
        $payment->setPayMethodPluginName($row['payment_method_plugin_name']);

        return $payment;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\payment\ojs\OJSCompletedPaymentDAO', '\OJSCompletedPaymentDAO');
}
