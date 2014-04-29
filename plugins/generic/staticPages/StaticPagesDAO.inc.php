<?php
/**
 * @file plugins/generic/staticPages/StaticPagesDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesDAO
 *
 * Operations for retrieving and modifying StaticPages objects.
 *
 */
import('lib.pkp.classes.db.DAO');

class StaticPagesDAO extends DAO {
	/** @var $parentPluginName Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function StaticPagesDAO($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	function getStaticPage($staticPageId) {
		$result =& $this->retrieve(
			'SELECT * FROM static_pages WHERE static_page_id = ?', $staticPageId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnStaticPageFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	function &getStaticPagesByJournalId($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT * FROM static_pages WHERE journal_id = ?', $journalId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnStaticPageFromRow');
		return $returner;
	}

	function getStaticPageByPath($journalId, $path) {
		$result =& $this->retrieve(
			'SELECT * FROM static_pages WHERE journal_id = ? AND path = ?', array($journalId, $path)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnStaticPageFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	function insertStaticPage(&$staticPage) {
		$this->update(
			'INSERT INTO static_pages
				(journal_id, path)
				VALUES
				(?, ?)',
			array(
				$staticPage->getJournalId(),
				$staticPage->getPath()
			)
		);

		$staticPage->setId($this->getInsertStaticPageId());
		$this->updateLocaleFields($staticPage);

		return $staticPage->getId();
	}

	function updateStaticPage(&$staticPage) {
		$returner = $this->update(
			'UPDATE static_pages
				SET
					journal_id = ?,
					path = ?
				WHERE static_page_id = ?',
				array(
					$staticPage->getJournalId(),
					$staticPage->getPath(),
					$staticPage->getId()
					)
			);
		$this->updateLocaleFields($staticPage);
		return $returner;
	}

	function deleteStaticPageById($staticPageId) {
		$returner = $this->update(
			'DELETE FROM static_pages WHERE static_page_id = ?', $staticPageId
		);
		return $this->update(
			'DELETE FROM static_page_settings WHERE static_page_id = ?', $staticPageId
		);
	}

	function &_returnStaticPageFromRow(&$row) {
		$staticPagesPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$staticPagesPlugin->import('StaticPage');

		$staticPage = new StaticPage();
		$staticPage->setId($row['static_page_id']);
		$staticPage->setPath($row['path']);
		$staticPage->setJournalId($row['journal_id']);

		$this->getDataObjectSettings('static_page_settings', 'static_page_id', $row['static_page_id'], $staticPage);
		return $staticPage;
	}

	function getInsertStaticPageId() {
		return $this->getInsertId('static_pages', 'static_page_id');
	}

	/**
	 * Get field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'content');
	}

	/**
	 * Update the localized data for this object
	 * @param $author object
	 */
	function updateLocaleFields(&$staticPage) {
		$this->updateDataObjectSettings('static_page_settings', $staticPage, array(
			'static_page_id' => $staticPage->getId()
		));
	}

	/**
	 * Find duplicate path
	 * @param $path String
	 * @param journalId int
	 * @param $staticPageId	int
	 * @return boolean
	 */
	function duplicatePathExists ($path, $journalId, $staticPageId = null) {
		$params = array(
					$journalId,
					$path
					);
		if (isset($staticPageId)) $params[] = $staticPageId;

		$result = $this->retrieve(
			'SELECT *
				FROM static_pages
				WHERE journal_id = ?
				AND path = ?' .
				(isset($staticPageId)?' AND NOT (static_page_id = ?)':''),
				$params
			);

		if($result->RecordCount() == 0) {
			// no duplicate exists
			$returner = false;
		} else {
			$returner = true;
		}
		return $returner;
	}
}
?>
