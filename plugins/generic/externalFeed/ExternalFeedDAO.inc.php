<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedDAO
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Operations for retrieving and modifying ExternalFeed objects.
 */

import('lib.pkp.classes.db.DAO');

class ExternalFeedDAO extends DAO {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ExternalFeedDAO($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Retrieve an ExternalFeed by ID.
	 * @param $feedId int
	 * @return ExternalFeed
	 */
	function &getExternalFeed($feedId) {
		$result =& $this->retrieve(
			'SELECT * FROM external_feeds WHERE feed_id = ?', $feedId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnExternalFeedFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve external feed journal ID by feed ID.
	 * @param $feedId int
	 * @return int
	 */
	function getExternalFeedJournalId($feedId) {
		$result =& $this->retrieve(
			'SELECT journal_id FROM external_feeds WHERE feed_id = ?', $feedId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Internal function to return ExternalFeed object from a row.
	 * @param $row array
	 * @return ExternalFeed
	 */
	function &_returnExternalFeedFromRow(&$row) {
		$externalFeedPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$externalFeedPlugin->import('ExternalFeed');

		$externalFeed = new ExternalFeed();
		$externalFeed->setId($row['feed_id']);
		$externalFeed->setJournalId($row['journal_id']);
		$externalFeed->setUrl($row['url']);
		$externalFeed->setSeq($row['seq']);
		$externalFeed->setDisplayHomepage($row['display_homepage']);
		$externalFeed->setDisplayBlock($row['display_block']);
		$externalFeed->setLimitItems($row['limit_items']);
		$externalFeed->setRecentItems($row['recent_items']);

		$this->getDataObjectSettings(
			'external_feed_settings',
			'feed_id',
			$row['feed_id'],
			$externalFeed
		);

		return $externalFeed;
	}

	/**
	 * Insert a new external feed.
	 * @param $externalFeed ExternalFeed
	 * @return int 
	 */
	function insertExternalFeed(&$externalFeed) {
		$ret = $this->update(
			'INSERT INTO external_feeds
				(journal_id,
				url,
				seq,
				display_homepage,
				display_block,
				limit_items,
				recent_items)
			VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$externalFeed->getJournalId(),
				$externalFeed->getUrl(),
				$externalFeed->getSeq(),
				$externalFeed->getDisplayHomepage(),
				$externalFeed->getDisplayBlock(),
				$externalFeed->getLimitItems(),
				$externalFeed->getRecentItems()
			)
		);
		$externalFeed->setId($this->getInsertExternalFeedId());

		$this->updateLocaleFields($externalFeed);

		return $externalFeed->getId();
	}

	/**
	 * Get a list of fields for which localized data is supported
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title');
	}

	/**
	 * Update the localized fields for this object.
	 * @param $externalFeed
	 */
	function updateLocaleFields(&$externalFeed) {
		$this->updateDataObjectSettings('external_feed_settings', $externalFeed, array(
			'feed_id' => $externalFeed->getId()
		));
	}

	/**
	 * Update an existing external feed.
	 * @param $externalFeed ExternalFeed
	 * @return boolean
	 */
	function updateExternalFeed(&$externalFeed) {
		$this->update(
			'UPDATE external_feeds
				SET
					journal_id = ?,
					url = ?,
					seq = ?,
					display_homepage = ?,
					display_block = ?,
					limit_items = ?,
					recent_items = ?
			WHERE feed_id = ?',
			array(
				$externalFeed->getJournalId(),
				$externalFeed->getUrl(),
				$externalFeed->getSeq(),
				$externalFeed->getDisplayHomepage(),
				$externalFeed->getDisplayBlock(),
				$externalFeed->getLimitItems(),
				$externalFeed->getRecentItems(),
				$externalFeed->getId()
			)
		);

		$this->updateLocaleFields($externalFeed);
	}

	/**
	 * Delete external feed.
	 * @param $externalFeed ExternalFeed 
	 * @return boolean
	 */
	function deleteExternalFeed($externalFeed) {
		return $this->deleteExternalFeedById($externalFeed->getId());
	}

	/**
	 * Delete external feed by ID.
	 * @param $feedId int
	 * @return boolean
	 */
	function deleteExternalFeedById($feedId) {
		$this->update(
			'DELETE FROM external_feeds WHERE feed_id = ?', $feedId
		);

		$this->update(
			'DELETE FROM external_feed_settings WHERE feed_id = ?', $feedId
		);
	}

	/**
	 * Delete external_feed by journal ID.
	 * @param $journalId int
	 */
	function deleteExternalFeedsByJournalId($journalId) {
		$feeds =& $this->getExternalFeedsByJournalId($journalId);

		while ($feed =& $feeds->next()) {
			$this->deleteExternalFeedById($feed->getId());
		}
	}

	/**
	 * Retrieve external feeds matching a particular journal ID.
	 * @param $journalId int
	 * @param $rangeInfo object DBRangeInfo object describing range of results to return
	 * @return object DAOResultFactory containing matching ExternalFeeds 
	 */
	function &getExternalFeedsByJournalId($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM external_feeds WHERE journal_id = ? ORDER BY seq ASC',
			$journalId,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnExternalFeedFromRow');
		return $returner;
	}

	/**
	 * Sequentially renumber external feeds in their sequence order.
	 */
	function resequenceExternalFeeds($journalId) {
		$result =& $this->retrieve(
			'SELECT feed_id FROM external_feeds WHERE journal_id = ? ORDER BY seq',
			$journalId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($feedId) = $result->fields;
			$this->update(
				'UPDATE external_feeds SET seq = ? WHERE feed_id = ?',
				array(
					$i,
					$feedId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted external feed.
	 * @return int
	 */
	function getInsertExternalFeedId() {
		return $this->getInsertId('external_feeds', 'feed_id');
	}
}

?>
