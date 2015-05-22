<?php

/**
 * @file classes/subscription/SubscriptionTypeDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 * @return SubscriptionType
	 */
	function &getSubscriptionType($typeId) {
		$result =& $this->retrieve(
			'SELECT * FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSubscriptionTypeFromRow($result->GetRowAssoc(false));
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
		$result =& $this->retrieve(
			'SELECT journal_id FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;	

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
		$result =& $this->retrieve(
			'SELECT COALESCE(l.setting_value, p.setting_value) FROM subscription_type_settings l LEFT JOIN subscription_type_settings p ON (p.type_id = ? AND p.setting_name = ? AND p.locale = ?) WHERE l.type_id = ? AND l.setting_name = ? AND l.locale = ?', 
			array(
				$typeId, 'name', AppLocale::getLocale(),
				$typeId, 'name', AppLocale::getPrimaryLocale()
			)
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
		$result =& $this->retrieve(
			'SELECT institutional FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

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
		$result =& $this->retrieve(
			'SELECT membership FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve nonExpiring flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeNonExpiring($typeId) {
		$result =& $this->retrieve(
			'SELECT non_expiring FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve public display flag by ID.
	 * @param $typeId int
	 * @return int
	 */
	function getSubscriptionTypeDisablePublicDisplay($typeId) {
		$result =& $this->retrieve(
			'SELECT disable_public_display FROM subscription_types WHERE type_id = ?', $typeId
		);

		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

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
		$result =& $this->retrieve(
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
	 * Internal function to return a SubscriptionType object from a row.
	 * @param $row array
	 * @return SubscriptionType
	 */
	function &_returnSubscriptionTypeFromRow(&$row) {
		$subscriptionType = new SubscriptionType();
		$subscriptionType->setTypeId($row['type_id']);
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

		HookRegistry::call('SubscriptionTypeDAO::_returnSubscriptionTypeFromRow', array(&$subscriptionType, &$row));

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
			'type_id' => $subscriptionType->getTypeId()
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

		$subscriptionType->setTypeId($this->getInsertSubscriptionTypeId());
		$this->updateLocaleFields($subscriptionType);
		return $subscriptionType->getTypeId();
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
				$subscriptionType->getTypeId()
			)
		);
		$this->updateLocaleFields($subscriptionType);
		return $returner;
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
		// Delete all subscriptions corresponding to this subscription type
		$institutional = $this->getSubscriptionTypeInstitutional($typeId);

		if ($institutional) {
			$subscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		} else {
			$subscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		}
		$returner = $subscriptionDao->deleteSubscriptionsByTypeId($typeId);

		// Delete subscription type
		if ($returner) {
			$returner = $this->update('DELETE FROM subscription_types WHERE type_id = ?', $typeId);
		}

		// Delete all localization settings for this subscription type
		if ($returner) {
			$this->update('DELETE FROM subscription_type_settings WHERE type_id = ?', $typeId);
		}

		return $returner;
	}

	/**
	 * Delete subscription types by journal ID. Note that all subscriptions with
	 * corresponding types are also deleted.
	 * @param $journalId int
	 * @return boolean
	 */
	function deleteSubscriptionTypesByJournal($journalId) {
		$result =& $this->retrieve(
			'SELECT type_id
			 FROM   subscription_types
			 WHERE  journal_id = ?',
			 $journalId
		);

		$returner = false;

		if ($result->RecordCount() != 0) {
			$returner = true;
			while (!$result->EOF && $returner) {
				$typeId = $result->fields[0];
				$returner = $this->deleteSubscriptionTypeById($typeId);
				$result->moveNext();
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve subscription types matching a particular journal ID.
	 * @param $journalId int
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function &getSubscriptionTypesByJournalId($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM subscription_types WHERE journal_id = ? ORDER BY seq',
			$journalId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionTypeFromRow');
		return $returner;
	}

	/**
	 * Retrieve subscription types matching a particular journal ID and institutional flag.
	 * @param $journalId int
	 * @param $institutional bool
	 * @param $disablePublicDisplay bool
	 * @return object DAOResultFactory containing matching SubscriptionTypes
	 */
	function &getSubscriptionTypesByInstitutional($journalId, $institutional = false, $disablePublicDisplay = null, $rangeInfo = null) {
		if ($institutional) $institutional = 1; else $institutional = 0;

		if ($disablePublicDisplay === null) {
			$disablePublicDisplaySql = '';
		} elseif ($disablePublicDisplay) {
			$disablePublicDisplaySql = 'AND disable_public_display = 1';
		} else {
			$disablePublicDisplaySql = 'AND disable_public_display = 0';
		}

		$result =& $this->retrieveRange(
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

		$returner = new DAOResultFactory($result, $this, '_returnSubscriptionTypeFromRow');
		return $returner;
	}

	/**
	 * Check if at least one subscription type exists for a given journal by institutional flag.
	 * @param $journalId int
	 * @param $institutional bool
	 * @return boolean
	 */
	function subscriptionTypesExistByInstitutional($journalId, $institutional = false) {
		if ($institutional) $institutional = 1; else $institutional = 0;

		$result =& $this->retrieve(
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
		unset($result);

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
		$result =& $this->retrieve(
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
