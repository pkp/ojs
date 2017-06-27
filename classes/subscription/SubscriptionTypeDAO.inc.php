<?php

/**
 * @file classes/subscription/SubscriptionTypeDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypeDAO
 * @ingroup subscription
 * @see SubscriptionType
 *
 * @brief Operations for retrieving and modifying SubscriptionType objects.
 */

import('classes.subscription.SubscriptionType');

class SubscriptionTypeDAO extends DAO {
	/**
	 * Retrieve a subscription type by ID.
	 * @param $typeId int
	 * @param $journalId int optional
	 * @return SubscriptionType
	 */
	function getById($typeId, $journalId = null) {
		$params = array((int) $typeId);
		if ($journalId) $params[] = (int) $journalId;

		$result = $this->retrieve(
			'SELECT * FROM subscription_types WHERE type_id = ?' .
			($journalId?' AND journal_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve subscription type name by ID.
	 * @param $typeId int
	 * @return string
	 */
	function getSubscriptionTypeName($typeId) {
		$result = $this->retrieve(
			'SELECT COALESCE(l.setting_value, p.setting_value) FROM subscription_type_settings l LEFT JOIN subscription_type_settings p ON (p.type_id = ? AND p.setting_name = ? AND p.locale = ?) WHERE l.type_id = ? AND l.setting_name = ? AND l.locale = ?', 
			array(
				(int) $typeId, 'name', AppLocale::getLocale(),
				(int) $typeId, 'name', AppLocale::getPrimaryLocale()
			)
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve institutional flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeInstitutional($typeId) {
		$result = $this->retrieve(
			'SELECT institutional FROM subscription_types WHERE type_id = ?', (int) $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve membership flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeMembership($typeId) {
		$result = $this->retrieve(
			'SELECT membership FROM subscription_types WHERE type_id = ?', (int) $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve nonExpiring flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeNonExpiring($typeId) {
		$result = $this->retrieve(
			'SELECT non_expiring FROM subscription_types WHERE type_id = ?', (int) $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve public display flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeDisablePublicDisplay($typeId) {
		$result = $this->retrieve(
			'SELECT disable_public_display FROM subscription_types WHERE type_id = ?', (int) $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Check if a subscription type exists with the given type id for a journal.
	 * @param $typeId int
	 * @param $journalId int
	 * @return boolean
	 */
	function subscriptionTypeExistsByTypeId($typeId, $journalId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
				FROM subscription_types
				WHERE type_id = ?
				AND   journal_id = ?',
			array(
				(int) $typeId,
				(int) $journalId
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Internal function to return a SubscriptionType object from a row.
	 * @param $row array
	 * @return SubscriptionType
	 */
	function _fromRow($row) {
		$subscriptionType = new SubscriptionType();
		$subscriptionType->setId($row['type_id']);
		$subscriptionType->setJournalId($row['journal_id']);
		$subscriptionType->setCost($row['cost']);
		$subscriptionType->setCurrencyCodeAlpha($row['currency_code_alpha']);
		$subscriptionType->setNonExpiring($row['non_expiring']);
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
		return array('name', 'description');
	}

	/**
	 * Update the localized settings for this object
	 * @param $subscriptionType object
	 */
	function updateLocaleFields(&$subscriptionType) {
		$this->updateDataObjectSettings('subscription_type_settings', $subscriptionType, array(
			'type_id' => $subscriptionType->getId()
		));
	}

	/**
	 * Insert a new SubscriptionType.
	 * @param $subscriptionType SubscriptionType
	 * @return boolean 
	 */
	function insertSubscriptionType(&$subscriptionType) {
		$this->update(
			'INSERT INTO subscription_types
				(journal_id, cost, currency_code_alpha, non_expiring, duration, format, institutional, membership, disable_public_display, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$subscriptionType->getJournalId(),
				$subscriptionType->getCost(),
				$subscriptionType->getCurrencyCodeAlpha(),
				$subscriptionType->getNonExpiring(),
				$subscriptionType->getDuration(),
				$subscriptionType->getFormat(),
				$subscriptionType->getInstitutional(),
				$subscriptionType->getMembership(),
				$subscriptionType->getDisablePublicDisplay(),
				$subscriptionType->getSequence()
			)
		);

		$subscriptionType->setId($this->getInsertId());
		$this->updateLocaleFields($subscriptionType);
		return $subscriptionType->getId();
	}

	/**
	 * Update an existing subscription type.
	 * @param $subscriptionType SubscriptionType
	 * @return boolean
	 */
	function updateSubscriptionType(&$subscriptionType) {
		$returner = $this->update(
			'UPDATE subscription_types
				SET
					journal_id = ?,
					cost = ?,
					currency_code_alpha = ?,
					non_expiring = ?,
					duration = ?,
					format = ?,
					institutional = ?,
					membership = ?,
					disable_public_display = ?,
					seq = ?
				WHERE type_id = ?',
			array(
				$subscriptionType->getJournalId(),
				$subscriptionType->getCost(),
				$subscriptionType->getCurrencyCodeAlpha(),
				$subscriptionType->getNonExpiring(),
				$subscriptionType->getDuration(),
				$subscriptionType->getFormat(),
				$subscriptionType->getInstitutional(),
				$subscriptionType->getMembership(),
				$subscriptionType->getDisablePublicDisplay(),
				$subscriptionType->getSequence(),
				$subscriptionType->getId()
			)
		);
		$this->updateLocaleFields($subscriptionType);
		return $returner;
	}

	/**
	 * Delete a subscription type by ID. Note that all subscriptions with this
	 * type ID are also deleted.
	 * @param $typeId int Subscription type ID
	 * @param $journalId int Optional journal ID
	 * @return boolean true for success
	 */
	function deleteById($typeId, $journalId = null) {
		$subscriptionType = $this->getById($typeId, $journalId);
		if ($subscriptionType) {
			if ($subscriptionType->getInstitutional()) {
				$subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
			} else {
				$subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
			}
			$subscriptionDao->deleteById($typeId);
			$returner = $this->update('DELETE FROM subscription_types WHERE type_id = ?', (int) $typeId);
			$this->update('DELETE FROM subscription_type_settings WHERE type_id = ?', (int) $typeId);
			return true;
		}
		return false;
	}

	/**
	 * Delete subscription types by journal ID. Note that all subscriptions with
	 * corresponding types are also deleted.
	 * @param $journalId int
	 * @return boolean
	 */
	function deleteByJournal($journalId) {
		$result = $this->retrieve(
			'SELECT type_id
			 FROM   subscription_types
			 WHERE  journal_id = ?',
			 (int) $journalId
		);

		$returner = false;

		if ($result->RecordCount() != 0) {
			$returner = true;
			while (!$result->EOF && $returner) {
				$typeId = $result->fields[0];
				$returner = $this->deleteSubscriptionTypeById($typeId);
				$result->MoveNext();
			}
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve subscription types matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function getByJournalId($journalId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM subscription_types WHERE journal_id = ? ORDER BY seq',
			(int) $journalId, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve subscription types matching a particular journal ID and institutional flag.
	 * @param $journalId int
	 * @param $institutional bool
	 * @param $disablePublicDisplay bool
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function getByInstitutional($journalId, $institutional = false, $disablePublicDisplay = null, $rangeInfo = null) {
		if ($institutional) $institutional = 1; else $institutional = 0;

		if ($disablePublicDisplay === null) {
			$disablePublicDisplaySql = '';
		} elseif ($disablePublicDisplay) {
			$disablePublicDisplaySql = 'AND disable_public_display = 1';
		} else {
			$disablePublicDisplaySql = 'AND disable_public_display = 0';
		}

		$result = $this->retrieveRange(
			'SELECT *
			FROM
			subscription_types
			WHERE journal_id = ?
			AND institutional = ? '
			. $disablePublicDisplaySql .
			' ORDER BY seq',
			array(
				$journalId,
				$institutional
			),
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
		if ($institutional) $institutional = 1; else $institutional = 0;

		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM
			subscription_types st
			WHERE st.journal_id = ?
			AND st.institutional = ?',
			array(
				$journalId,
				$institutional
			)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		return $returner;
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
		$result = $this->retrieve(
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

			$result->MoveNext();
		}

		$result->Close();
	}
}

?>
