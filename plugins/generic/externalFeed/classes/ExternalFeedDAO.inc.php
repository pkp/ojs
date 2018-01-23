<?php

/**
 * @file plugins/generic/externalFeed/classes/ExternalFeedDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedDAO
 * @ingroup plugins_generic_externalFeed_classes
 *
 * @brief Operations for retrieving and modifying ExternalFeed objects.
 */

import('lib.pkp.classes.db.DAO');

class ExternalFeedDAO extends DAO {
	/** @var ExternalFeedPlugin reference to ExternalFeed plugin */
	protected $parentPlugin = null;

	/**
	 * Constructor
	 */
	public function __construct($parentPlugin) {
		$this->parentPlugin = $parentPlugin;
		parent::__construct();
	}

	/**
	 * Instantiate a new data object.
	 * @return ExternalFeed
	 */
	function newDataObject() {
		$this->parentPlugin->import('classes.ExternalFeed');
		return new ExternalFeed();
	}

	/**
	 * Retrieve an ExternalFeed by ID.
	 * @param $feedId int
	 * @param $contextId int 
	 * @return ExternalFeed
	 */
	public function getById($feedId, $contextId = null) {
		$params = array((int) $feedId);
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT * FROM external_feeds WHERE feed_id = ? ' . ($contextId?' AND journal_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_returnExternalFeedFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve external feed journal ID by feed ID.
	 * @param $feedId int
	 * @return int
	 */
	public function getExternalFeedJournalId($feedId) {
		$result = $this->retrieve(
			'SELECT journal_id FROM external_feeds WHERE feed_id = ?', $feedId
		);

		return isset($result->fields[0]) ? $result->fields[0] : 0;	
	}

	/**
	 * Internal function to return ExternalFeed object from a row.
	 * @param $row array
	 * @return ExternalFeed
	 */
	public function _returnExternalFeedFromRow($row) {
		$externalFeed = $this->newDataObject();
		$externalFeed->setId($row['feed_id']);
		$externalFeed->setJournalId($row['journal_id']);
		$externalFeed->setUrl($row['url']);
		$externalFeed->setSequence($row['seq']);
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
	public function insertObject($externalFeed) {
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
				$externalFeed->getSequence(),
				$externalFeed->getDisplayHomepage(),
				$externalFeed->getDisplayBlock(),
				$externalFeed->getLimitItems(),
				$externalFeed->getRecentItems()
			)
		);
		$externalFeed->setId($this->getInsertId());

		$this->updateLocaleFields($externalFeed);

		return $externalFeed->getId();
	}

	/**
	 * Get a list of fields for which localized data is supported
	 * @return array
	 */
	public function getLocaleFieldNames() {
		return array('title');
	}

	/**
	 * Update the localized fields for this object.
	 * @param $externalFeed
	 */
	public function updateLocaleFields(&$externalFeed) {
		$this->updateDataObjectSettings('external_feed_settings', $externalFeed, array(
			'feed_id' => $externalFeed->getId()
		));
	}

	/**
	 * Update an existing external feed.
	 * @param $externalFeed ExternalFeed
	 * @return boolean
	 */
	public function updateExternalFeed(&$externalFeed) {
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
				$externalFeed->getSequence(),
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
	public function deleteExternalFeed($externalFeed) {
		return $this->deleteExternalFeedById($externalFeed->getId());
	}

	/**
	 * Delete external feed by ID.
	 * @param $feedId int
	 * @return boolean
	 */
	public function deleteExternalFeedById($feedId) {
		$this->update(
			'DELETE FROM external_feeds WHERE feed_id = ?', $feedId
		);

		$this->update(
			'DELETE FROM external_feed_settings WHERE feed_id = ?', $feedId
		);
	}

	/**
	 * Delete external_feed by context ID.
	 * @param $contextId int
	 */
	public function deleteByContextId($contextId) {
		$feeds = $this->getByContextId($contextId);

		while ($feed = $feeds->next()) {
			$this->deleteExternalFeedById($feed->getId());
		}
	}

	/**
	 * Retrieve external feeds matching a particular context ID.
	 * @param $contextId int
	 * @param $rangeInfo object DBRangeInfo object describing range of results to return
	 * @return object DAOResultFactory containing matching ExternalFeeds 
	 */
	public function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM external_feeds WHERE journal_id = ? ORDER BY seq ASC',
			$contextId,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_returnExternalFeedFromRow');
	}

	/**
	 * Sequentially renumber external feeds in their sequence order.
	 */
	public function resequenceExternalFeeds($journalId) {
		$result = $this->retrieve(
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

			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Get the ID of the last inserted external feed.
	 * @return int
	 */
	public function getInsertId() {
		return $this->_getInsertId('external_feeds', 'feed_id');
	}
}

?>
