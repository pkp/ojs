<?php

/**
 * @file plugins/generic/referral/ReferralDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferralDAO
 * @ingroup plugins_generic_referral
 * @see Referral
 *
 * @brief Operations for retrieving and modifying Referral objects.
 */

// $Id$


class ReferralDAO extends DAO {
	/**
	 * Retrieve an referral by referral ID.
	 * @param $referralId int
	 * @return Referral
	 */
	function &getReferral($referralId) {
		$result =& $this->retrieve(
			'SELECT * FROM referrals WHERE referral_id = ?', $referralId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnReferralFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get a list of localized field names
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name');
	}

	/**
	 * Internal function to return a Referral object from a row.
	 * @param $row array
	 * @return Referral
	 */
	function &_returnReferralFromRow(&$row) {
		$referral = new Referral();
		$referral->setReferralId($row['referral_id']);
		$referral->setArticleId($row['article_id']);
		$referral->setStatus($row['status']);
		$referral->setUrl($row['url']);
		$referral->setDateAdded($this->datetimeFromDB($row['date_added']));
		$referral->setLinkCount($row['link_count']);

		$this->getDataObjectSettings('referral_settings', 'referral_id', $row['referral_id'], $referral);

		return $referral;
	}

	/**
	 * Check if a referrer exists with the given article and URL.
	 * @param $articleId int
	 * @param $url string
	 * @return boolean
	 */
	function referralExistsByUrl($articleId, $url) {
		$result =& $this->retrieve(
			'SELECT	COUNT(*)
			FROM	referrals
			WHERE	article_id = ? AND
				url = ?',
			array(
				(int) $articleId,
				$url
			)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Increment the referral count.
	 * @param $articleId int
	 * @param $url string
	 * @return int 1 iff the referral exists
	 */
	function incrementReferralCount($articleId, $url) {
		return $this->update(
			'UPDATE referrals SET link_count = link_count + 1 WHERE article_id = ? AND url = ?',
			array((int) $articleId, $url)
		);
	}

	/**
	 * Update the localized settings for this object
	 * @param $referral object
	 */
	function updateLocaleFields(&$referral) {
		$this->updateDataObjectSettings('referral_settings', $referral, array(
			'referral_id' => $referral->getReferralId()
		));
	}

	/**
	 * Insert a new Referral.
	 * @param $referral Referral
	 * @return int 
	 */
	function insertReferral(&$referral) {
		$this->update(
			sprintf(
				'INSERT INTO referrals
					(status, article_id, url, date_added, link_count)
				VALUES
					(?, ?, ?, %s, ?)',
				$this->datetimeToDB($referral->getDateAdded())
			),
			array(
				(int) $referral->getStatus(),
				(int) $referral->getArticleId(),
				$referral->getUrl(),
				(int) $referral->getLinkCount()
			)
		);
		$referral->setReferralId($this->getInsertReferralId());
		$this->updateLocaleFields($referral);
		return $referral->getReferralId();
	}

	/**
	 * Update an existing referral.
	 * @param $referral Referral
	 * @return boolean
	 */
	function updateReferral(&$referral) {
		$returner = $this->update(
			sprintf('UPDATE	referrals
				SET	status = ?,
					article_id = ?,
					url = ?,
					date_added = %s,
					link_count = ?
				WHERE	referral_id = ?',
				$this->datetimeToDB($referral->getDateAdded())
			),
			array(
				(int) $referral->getStatus(),
				(int) $referral->getArticleId(),
				$referral->getUrl(),
				(int) $referral->getLinkCount(),
				(int) $referral->getReferralId()
			)
		);
		$this->updateLocaleFields($referral);
		return $returner;
	}

	/**
	 * Delete a referral.
	 * deleted.
	 * @param $referral Referral 
	 * @return boolean
	 */
	function deleteReferral($referral) {
		return $this->deleteReferralById($referral->getTypeId());
	}

	/**
	 * Delete a referral by referral ID.
	 * @param $referralId int
	 * @return boolean
	 */
	function deleteReferralById($referralId) {
		$this->update('DELETE FROM referral_settings WHERE referral_id = ?', $referralId);
		return $this->update('DELETE FROM referrals WHERE referral_id = ?', $referralId);
	}

	/**
	 * Retrieve an iterator of referrals for a particular user ID,
	 * optionally filtering by status.
	 * @param $userId int
	 * $param $status int
	 * @return object DAOResultFactory containing matching Referrals
	 */
	function &getReferralsByUserId($userId, $status = null, $rangeInfo = null) {
		$params = array((int) $userId);
		if ($status !== null) $params[] = (int) $status;
		$result =& $this->retrieveRange(
			'SELECT	r.*
			FROM	referrals r,
				articles a
			WHERE	r.article_id = a.article_id AND
				a.user_id = ?' .
				($status !== null?' AND r.status = ?':'') . '
			ORDER BY r.date_added',
			$params
		);

		$returner = new DAOResultFactory($result, $this, '_returnReferralFromRow');
		return $returner;
	}

	/**
	 * Retrieve an iterator of published referrals for a particular user article
	 * @param $articleId int
	 * $param $rangeInfo RangeInfo
	 * @return object DAOResultFactory containing matching Referrals
	 */
	function &getPublishedReferralsForArticle($articleId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT	r.*
			FROM	referrals r
			WHERE	r.article_id = ? AND
				r.status = ?',
			array((int) $articleId, REFERRAL_STATUS_ACCEPT),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnReferralFromRow');
		return $returner;
	}

	/**
	 * Get the ID of the last inserted referral.
	 * @return int
	 */
	function getInsertReferralId() {
		return $this->getInsertId('referrals', 'referral_id');
	}
}

?>
