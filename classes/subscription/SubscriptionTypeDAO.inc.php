<?php

/**
 * @file classes/subscription/SubscriptionTypeDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypeDAO
 * @ingroup subscription
 * @see SubscriptionType
 *
 * @brief Operations for retrieving and modifying SubscriptionType objects.
 */

import('classes.subscription.SubscriptionType');

use Illuminate\Database\Capsule\Manager as Capsule;

class SubscriptionTypeDAO extends DAO {
	/**
	 * Create a new subscription type.
	 * @return SubscriptionType
	 */
	function newDataObject() {
		return new SubscriptionType();
	}

	/**
	 * Retrieve a subscription type by ID.
	 * @param $typeId int
	 * @param $journalId int optional
	 * @return SubscriptionType
	 */
	function getById($typeId, $journalId = null) {
		$params = [(int) $typeId];
		if ($journalId) $params[] = (int) $journalId;

		$result = $this->retrieve(
			'SELECT * FROM subscription_types WHERE type_id = ?' .
			($journalId?' AND journal_id = ?':''),
			$params
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Retrieve subscription type name by ID.
	 * @param $typeId int
	 * @return string?
	 */
	function getSubscriptionTypeName($typeId) {
		$result = $this->retrieve(
			'SELECT COALESCE(l.setting_value, p.setting_value) as subscription_type_name FROM subscription_type_settings l LEFT JOIN subscription_type_settings p ON (p.type_id = ? AND p.setting_name = ? AND p.locale = ?) WHERE l.type_id = ? AND l.setting_name = ? AND l.locale = ?',
			[
				(int) $typeId, 'name', AppLocale::getLocale(),
				(int) $typeId, 'name', AppLocale::getPrimaryLocale()
			]
		);
		$row = $result->current();
		return $row ? $row->subscription_type_name : null;
	}

	/**
	 * Retrieve institutional flag by ID.
	 * @param $typeId int
	 * @return boolean
	 */
	function getSubscriptionTypeInstitutional($typeId) {
		$result = $this->retrieve(
			'SELECT institutional FROM subscription_types WHERE type_id = ?', [(int) $typeId]
		);
		$row = $result->current();
		return $row ? (boolean) $row->institutional : false;
	}

	/**
	 * Retrieve membership flag by ID.
	 * @param $typeId int
	 * @return boolean
	 */
	function getSubscriptionTypeMembership($typeId) {
		$result = $this->retrieve(
			'SELECT membership FROM subscription_types WHERE type_id = ?', [(int) $typeId]
		);
		$row = $result->current();
		return $row ? (boolean) $row->membership : false;
	}

	/**
	 * Retrieve public display flag by ID.
	 * @param $typeId int
	 * @return boolean
	 */
	function getSubscriptionTypeDisablePublicDisplay($typeId) {
		$result = $this->retrieve(
			'SELECT disable_public_display FROM subscription_types WHERE type_id = ?', [(int) $typeId]
		);
		$row = $result->current();
		return $row ? (boolean) $row->disable_public_display : false;
	}

	/**
	 * Check if a subscription type exists with the given type id for a journal.
	 * @param $typeId int
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionTypeExistsByTypeId($typeId, $journalId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) AS row_count
				FROM subscription_types
				WHERE type_id = ?
				AND   journal_id = ?',
			[(int) $typeId, (int) $journalId]
		);
		$row = $result->current();
		return $row ? (boolean) $row->row_count : false;
	}

	/**
	 * Internal function to return a SubscriptionType object from a row.
	 * @param $row array
	 * @return SubscriptionType
	 */
	function _fromRow($row) {
		$subscriptionType = $this->newDataObject();
		$subscriptionType->setId($row['type_id']);
		$subscriptionType->setJournalId($row['journal_id']);
		$subscriptionType->setCost($row['cost']);
		$subscriptionType->setCurrencyCodeAlpha($row['currency_code_alpha']);
		$subscriptionType->setDuration($row['duration']);
		$subscriptionType->setFormat($row['format']);
		$subscriptionType->setInstitutional($row['institutional']);
		$subscriptionType->setMembership($row['membership']);
		$subscriptionType->setDisablePublicDisplay($row['disable_public_display']);
		$subscriptionType->setSequence($row['seq']);

		$this->getDataObjectSettings('subscription_type_settings', 'type_id', $row['type_id'], $subscriptionType);

		HookRegistry::call('SubscriptionTypeDAO::_fromRow', array(&$subscriptionType, &$row));

		return $subscriptionType;
	}

	/**
	 * Get the list of field names for which localized data is used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return ['name', 'description'];
	}

	/**
	 * Update the localized settings for this object
	 * @param $subscriptionType object
	 */
	function updateLocaleFields($subscriptionType) {
		$this->updateDataObjectSettings('subscription_type_settings', $subscriptionType, [
			'type_id' => $subscriptionType->getId()
		]);
	}

	/**
	 * Insert a new SubscriptionType.
	 * @param $subscriptionType SubscriptionType
	 * @return int Inserted subscription type ID
	 */
	function insertObject($subscriptionType) {
		$this->update(
			'INSERT INTO subscription_types
				(journal_id, cost, currency_code_alpha, duration, format, institutional, membership, disable_public_display, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			[
				(int) $subscriptionType->getJournalId(),
				(float) $subscriptionType->getCost(),
				$subscriptionType->getCurrencyCodeAlpha(),
				$subscriptionType->getDuration(),
				$subscriptionType->getFormat(),
				(int) $subscriptionType->getInstitutional(),
				$subscriptionType->getMembership(),
				(int) $subscriptionType->getDisablePublicDisplay(),
				(float) $subscriptionType->getSequence(),
			]
		);

		$subscriptionType->setId($this->getInsertId());
		$this->updateLocaleFields($subscriptionType);
		return $subscriptionType->getId();
	}

	/**
	 * Update an existing subscription type.
	 * @param $subscriptionType SubscriptionType
	 */
	function updateObject($subscriptionType) {
		$this->update(
			'UPDATE subscription_types
				SET
					journal_id = ?,
					cost = ?,
					currency_code_alpha = ?,
					duration = ?,
					format = ?,
					institutional = ?,
					membership = ?,
					disable_public_display = ?,
					seq = ?
				WHERE type_id = ?',
			[
				(int) $subscriptionType->getJournalId(),
				$subscriptionType->getCost(),
				$subscriptionType->getCurrencyCodeAlpha(),
				$subscriptionType->getDuration(),
				$subscriptionType->getFormat(),
				(int) $subscriptionType->getInstitutional(),
				$subscriptionType->getMembership(),
				(int) $subscriptionType->getDisablePublicDisplay(),
				(float) $subscriptionType->getSequence(),
				(int) $subscriptionType->getId(),
			]
		);
		$this->updateLocaleFields($subscriptionType);
	}

	/**
	 * Delete a subscription type by ID. Note that all subscriptions with this
	 * type ID are also deleted.
	 * @param $typeId int Subscription type ID
	 * @param $journalId int Optional journal ID
	 */
	function deleteById($typeId, $journalId = null) {
		$subscriptionType = $this->getById($typeId, $journalId);
		if ($subscriptionType) {
			$subscriptionDao = DAORegistry::getDAO($subscriptionType->getInstitutional()?'InstitutionalSubscriptionDAO':'IndividualSubscriptionDAO');
			$subscriptionDao->deleteById($typeId);
			$this->update('DELETE FROM subscription_types WHERE type_id = ?', [(int) $typeId]);
			$this->update('DELETE FROM subscription_type_settings WHERE type_id = ?', [(int) $typeId]);
		}
	}

	/**
	 * Delete subscription types by journal ID. Note that all subscriptions with
	 * corresponding types are also deleted.
	 * @param $journalId int
	 */
	function deleteByJournal($journalId) {
		$result = $this->retrieve(
			'SELECT type_id
			 FROM   subscription_types
			 WHERE  journal_id = ?',
			 [(int) $journalId]
		);
		foreach ($result as $row) {
			$typeId = $row->type_id;
			$this->deleteById($typeId);
		}
	}

	/**
	 * Retrieve subscription types matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function getByJournalId($journalId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			$sql = 'SELECT * FROM subscription_types WHERE journal_id = ? ORDER BY seq',
			$params = [(int) $journalId],
			$rangeInfo);
		return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params, $rangeInfo); // Counted in subscription type grid paging
	}

	/**
	 * Retrieve subscription types matching a particular journal ID and institutional flag.
	 * @param $journalId int
	 * @param $institutional bool
	 * @param $disablePublicDisplay bool|null
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function getByInstitutional($journalId, $institutional = false, $disablePublicDisplay = null, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT	*
			FROM subscription_types
			WHERE	journal_id = ?
				AND institutional = ?
				' . ($disablePublicDisplay===true?'AND disable_public_display = 1':'') . '
				' . ($disablePublicDisplay===false?'AND disable_public_display = 0':'') . '
			ORDER BY seq',
			[(int) $journalId, (int) $institutional],
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Check if at least one subscription type exists for a given journal by institutional flag.
	 * @param $journalId int
	 * @param $institutional bool
	 * @return boolean
	 */
	function subscriptionTypesExistByInstitutional($journalId, $institutional = false) {
		$result = Capsule::table('subscription_types')
			->where('journal_id', (int) $journalId)
			->where('institutional', (int) $institutional)
			->first();

		return is_null($result) ? false : true;
	}

	/**
	 * Get the ID of the last inserted subscription type.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('subscription_types', 'type_id');
	}

	/**
	 * Sequentially renumber subscription types in their sequence order.
	 */
	function resequenceSubscriptionTypes($journalId) {
		$result = $this->retrieve('SELECT type_id FROM subscription_types WHERE journal_id = ? ORDER BY seq', [(int) $journalId]);

		for ($i=1; $row = $result->current(); $i++) {
			$this->update('UPDATE subscription_types SET seq = ? WHERE type_id = ?', [(int) $i, (int) $row->type_id]);
			$result->next();
		}
	}
}


