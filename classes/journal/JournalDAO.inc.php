<?php

/**
 * JournalDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Class for Journal DAO.
 * Operations for retrieving and modifying Journal objects.
 *
 * $Id$
 */

class JournalDAO extends DAO {

	/**
	 * Constructor.
	 */
	function JournalDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a journal by ID.
	 * @param $journalId int
	 * @return Journal
	 */
	function &getJournal($journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM journals WHERE journal_id = ?', $journalId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnJournalFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a journal by path.
	 * @param $path string
	 * @return Journal
	 */
	function &getJournalByPath($path) {
		$result = &$this->retrieve(
			'SELECT * FROM journals WHERE path = ?', $path
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnJournalFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a Journal object from a row.
	 * @param $row array
	 * @return Journal
	 */
	function &_returnJournalFromRow(&$row) {
		$journal = &new Journal();
		$journal->setJournalId($row['journal_id']);
		$journal->setDescription($row['description']);
		$journal->setPath($row['path']);
		$journal->setSequence($row['seq']);
		$journal->setEnabled($row['enabled']);
		
		return $journal;
	}

	/**
	 * Insert a new journal.
	 * @param $journal Journal
	 */	
	function insertJournal(&$journal) {
		return $this->update(
			'INSERT INTO journals
				(description, path, seq)
				VALUES
				(?, ?, ?)',
			array(
				$journal->getDescription(),
				$journal->getPath(),
				$journal->getSequence() == null ? 0 : $journal->getSequence()
			)
		);
	}
	
	/**
	 * Update an existing journal.
	 * @param $journal Journal
	 */
	function updateJournal(&$journal) {
		return $this->update(
			'UPDATE journals
				SET
					description = ?,
					path = ?,
					seq = ?,
					enabled = ?
				WHERE journal_id = ?',
			array(
				$journal->getDescription(),
				$journal->getPath(),
				$journal->getSequence(),
				$journal->getEnabled(),
				$journal->getJournalId()
			)
		);
	}
	
	/**
	 * Delete a journal.
	 * @param $journal Journal
	 */
	function deleteJournal(&$journal) {
		return $this->deleteJournalById($journal->getJournalId());
	}
	
	/**
	 * Delete a journal by ID.
	 * @param $journalId int
	 */
	function deleteJournalById($journalId) {
		return $this->update(
			'DELETE FROM journals WHERE journal_id = ?', $journalId
		);
	}
	
	/**
	 * Retrieve all journals.
	 * @return array Journals ordered by sequence
	 */
	function &getJournals() {
		$journals = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM journals ORDER BY seq'
		);
		
		while (!$result->EOF) {
			$row = &$result->GetRowAssoc(false);
			$journals[$row['journal_id']] = &$this->_returnJournalFromRow(&$row);
			$result->moveNext();
		}
		$result->Close();
	
		return $journals;
	}
	
	/**
	 * Retrieve all enabled journals
	 * @return array Journals ordered by sequence
	 */
	 function &getEnabledJournals() 
	 {
		$journals = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM journals WHERE enabled=1 ORDER BY seq'
		);
		
		while (!$result->EOF) {
			$journals[] = &$this->_returnJournalFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $journals;
	}
	
	/**
	 * Retrieve the IDs and titles of all journals in an associative array.
	 * @return array
	 */
	function &getJournalTitles() {
		$journals = array();
		
		$result = &$this->retrieve(
			'SELECT journal_id, title FROM journals ORDER BY seq'
		);
		
		while (!$result->EOF) {
			$journals[$result->fields[0]] = $result->fields[1];
			$result->moveNext();
		}
		$result->Close();
	
		return $journals;
	}
	
	/**
	* Retrieve enabled journal IDs and titles in an associative array
	* @return array
	*/
	function &getEnabledJournalTitles()
	{
		$journals = array();
		
		$result = &$this->retrieve(
			'SELECT journal_id, title FROM journals WHERE enabled=1 ORDER BY seq'
		);
		
		while (!$result->EOF) {
			$journals[$result->fields[0]] = $result->fields[1];
			$result->moveNext();
		}
		$result->Close();
	
		return $journals;
	}
	
	/**
	 * Check if a journal exists with a specified path.
	 * @param $path the path of the journal
	 * @return boolean
	 */
	function journalExistsByPath($path) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM journals WHERE path = ?', $path
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Sequentially renumber journals in their sequence order.
	 */
	function resequenceJournals() {
		$result = &$this->retrieve(
			'SELECT journal_id FROM journals ORDER BY seq'
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($journalId) = $result->fields;
			$this->update(
				'UPDATE journals SET seq = ? WHERE journal_id = ?',
				array(
					$i,
					$journalId
				)
			);
			
			$result->moveNext();
		}

		$result->close();
	}
	
	/**
	 * Get the ID of the last inserted journal.
	 * @return int
	 */
	function getInsertJournalId() {
		return $this->getInsertId('journals', 'journal_id');
	}
	
}

?>
