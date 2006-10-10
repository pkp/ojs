<?php

/**
 * SubscriptionTypeDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package subscription
 *
 * Class for SubscriptionType DAO.
 * Operations for retrieving and modifying SubscriptionType objects.
 *
 * $Id$
 */

import('subscription.SubscriptionType');

class SubscriptionTypeDAO extends DAO {

	/**
	 * Constructor.
	 */
	function SubscriptionTypeDAO() {
		parent::DAO();
	}

	/**
	 * Retrieve a subscription type by ID.
	 * @param $typeId int
	 * @return SubscriptionType
	 */
	function &getSubscriptionType($typeId) {
		$result = &$this->retrieve(
			'SELECT * FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSubscriptionTypeFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve subscription type journal ID by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeJournalId($typeId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM subscription_types WHERE type_id = ?', $typeId
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;	

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve subscription type name by ID.
	 * @param $typeId int
	 * @return string
	 */
	function getSubscriptionTypeName($typeId) {
		$result = &$this->retrieve(
			'SELECT type_name FROM subscription_types WHERE type_id = ?', $typeId
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve institutional flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeInstitutional($typeId) {
		$result = &$this->retrieve(
			'SELECT institutional FROM subscription_types WHERE type_id = ?', $typeId
		);
		
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve membership flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeMembership($typeId) {
		$result = &$this->retrieve(
			'SELECT membership FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve public flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypePublic($typeId) {
		$result = &$this->retrieve(
			'SELECT pub FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a subscription type exists with the given type id for a journal.
	 * @param $typeId int
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionTypeExistsByTypeId($typeId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM subscription_types
				WHERE type_id = ?
				AND   journal_id = ?',
			array(
				$typeId,
				$journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if a subscription type exists with the given type name for a journal.
	 * @param $typeName string
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionTypeExistsByTypeName($typeName, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM subscription_types
				WHERE type_name = ?
				AND   journal_id = ?',
			array(
				$typeName,
				$journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Return subscription type ID based on a type name for a journal.
	 * @param $typeName string
	 * @param $journalId int
	 * @return int
	 */
	function getSubscriptionTypeByTypeName($typeName, $journalId) {
		$result = &$this->retrieve(
			'SELECT type_id
				FROM subscription_types
				WHERE type_name = ?
				AND   journal_id = ?',
			array(
				$typeName,
				$journalId
			)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a SubscriptionType object from a row.
	 * @param $row array
	 * @return SubscriptionType
	 */
	function &_returnSubscriptionTypeFromRow(&$row) {
		$subscriptionType = &new SubscriptionType();
		$subscriptionType->setTypeId($row['type_id']);
		$subscriptionType->setJournalId($row['journal_id']);
		$subscriptionType->setTypeName($row['type_name']);
		$subscriptionType->setDescription($row['description']);
		$subscriptionType->setCost($row['cost']);
		$subscriptionType->setCurrencyCodeAlpha($row['currency_code_alpha']);
		$subscriptionType->setDuration($row['duration']);
		$subscriptionType->setFormat($row['format']);
		$subscriptionType->setInstitutional($row['institutional']);
		$subscriptionType->setMembership($row['membership']);
		$subscriptionType->setPublic($row['pub']);
		$subscriptionType->setSequence($row['seq']);

		HookRegistry::call('SubscriptionTypeDAO::_returnSubscriptionTypeFromRow', array(&$subscriptionType, &$row));

		return $subscriptionType;
	}

	/**
	 * Insert a new SubscriptionType.
	 * @param $subscriptionType SubscriptionType
	 * @return boolean 
	 */
	function insertSubscriptionType(&$subscriptionType) {
		$ret = $this->update(
			'INSERT INTO subscription_types
				(journal_id, type_name, description, cost, currency_code_alpha, duration, format, institutional, membership, pub, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$subscriptionType->getJournalId(),
				$subscriptionType->getTypeName(),
				$subscriptionType->getDescription(),
				$subscriptionType->getCost(),
				$subscriptionType->getCurrencyCodeAlpha(),
				$subscriptionType->getDuration(),
				$subscriptionType->getFormat(),
				$subscriptionType->getInstitutional(),
				$subscriptionType->getMembership(),
				$subscriptionType->getPublic(),
				$subscriptionType->getSequence()
			)
		);
		
		$subscriptionType->setTypeId($this->getInsertSubscriptionTypeId());
		return $subscriptionType->getTypeId();
	}

	/**
	 * Update an existing subscription type.
	 * @param $subscriptionType SubscriptionType
	 * @return boolean
	 */
	function updateSubscriptionType(&$subscriptionType) {
		return $this->update(
			'UPDATE subscription_types
				SET
					journal_id = ?,
					type_name = ?,
					description = ?,
					cost = ?,
					currency_code_alpha = ?,
					duration = ?,
					format = ?,
					institutional = ?,
					membership = ?,
					pub = ?,
					seq = ?
				WHERE type_id = ?',
			array(
				$subscriptionType->getJournalId(),
				$subscriptionType->getTypeName(),
				$subscriptionType->getDescription(),
				$subscriptionType->getCost(),
				$subscriptionType->getCurrencyCodeAlpha(),
				$subscriptionType->getDuration(),
				$subscriptionType->getFormat(),
				$subscriptionType->getInstitutional(),
				$subscriptionType->getMembership(),
				$subscriptionType->getPublic(),
				$subscriptionType->getSequence(),
				$subscriptionType->getTypeId()
			)
		);
	}

	/**
	 * Delete a subscription type.
	 * @param $subscriptionType SubscriptionType
	 * @return boolean 
	 */
	function deleteSubscriptionType(&$subscriptionType) {
		return $this->deleteSubscriptionTypeById($subscriptionType->getTypeId());
	}

	/**
	 * Delete a subscription type by ID. Note that all subscriptions with this
	 * type ID are also deleted.
	 * @param $typeId int
	 * @return boolean
	 */
	function deleteSubscriptionTypeById($typeId) {
		// Delete subscription type
		$ret = $this->update(
			'DELETE FROM subscription_types WHERE type_id = ?', $typeId
			);

		// Delete all subscriptions with this subscription type
		if ($ret) {
			$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
			return $subscriptionDao->deleteSubscriptionByTypeId($typeId);
		} else {
			return $ret;
		}
	}

	/**
	 * Retrieve an array of subscription types matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function &getSubscriptionTypesByJournalId($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM subscription_types WHERE journal_id = ? ORDER BY seq',
			 $journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnSubscriptionTypeFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted subscription type.
	 * @return int
	 */
	function getInsertSubscriptionTypeId() {
		return $this->getInsertId('subscription_types', 'type_id');
	}

	/**
	 * Sequentially renumber subscription types in their sequence order.
	 */
	function resequenceSubscriptionTypes($journalId) {
		$result = &$this->retrieve(
			'SELECT type_id FROM subscription_types WHERE journal_id = ? ORDER BY seq',
			$journalId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($subscriptionTypeId) = $result->fields;
			$this->update(
				'UPDATE subscription_types SET seq = ? WHERE type_id = ?',
				array(
					$i,
					$subscriptionTypeId
				)
			);
			
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}

}

?>
