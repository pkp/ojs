<?php

/**
 * JournalSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Class for Journal Settings DAO.
 * Operations for retrieving and modifying journal settings.
 *
 * $Id$
 */

class JournalSettingsDAO extends DAO {
	
	/** Cached journal settings */
	var $journalSettings;
	
	/**
	 * Constructor.
	 */
	function JournalSettingsDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a journal setting value.
	 * @param $journalId int
	 * @param $name
	 * @return mixed
	 */
	function getSetting($journalId, $name) {
		if (!isset($this->journalSettings[$journalId])) {
			$this->getJournalSettings($journalId);
		}
		
		return isset($this->journalSettings[$journalId][$name]) ? $this->journalSettings[$journalId][$name] : null;
	}
	
	/**
	 * Retrieve and cache all settings for a journal.
	 * @param $journalId int
	 * @return array
	 */
	function &getJournalSettings($journalId) {
		$this->journalSettings[$journalId] = array();
		
		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM journal_settings WHERE journal_id = ?', $journalId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				switch ($row['setting_type']) {
					case 'bool':
						$value = (bool) $row['setting_value'];
						break;
					case 'int':
						$value = (int) $row['setting_value'];
						break;
					case 'float':
						$value = (float) $row['setting_value'];
						break;
					case 'object':
						$value = unserialize($row['setting_value']);
						break;
					case 'string':
					default:
						$value = $row['setting_value'];
						break;
				}
				$this->journalSettings[$journalId][$row['setting_name']] = $value;
				$result->MoveNext();
			}
			$result->close();
			
			return $this->journalSettings[$journalId];
		}
	}
	
	/**
	 * Add/update a journal setting.
	 * @param $journalId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 */
	function updateSetting($journalId, $name, $value, $type = null) {
		if ($type == null) {
			switch (gettype($value)) {
				case 'boolean':
				case 'bool':
					$type = 'bool';
					break;
				case 'integer':
				case 'int':
					$type = 'int';
					break;
				case 'double':
				case 'float':
					$type = 'float';
					break;
				case 'array':
				case 'object':
					$type = 'object';
					break;
				case 'string':
				default:
					$type = 'string';
					break;
			}
		}
		
		if ($type == 'object') {
			$value = serialize($value);
			
		} else if ($type == 'bool') {
			$value = isset($value) && $value ? 1 : 0;
		}
		
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM journal_settings WHERE journal_id = ? AND setting_name = ?',
			array($journalId, $name)
		);
		
		if ($result->fields[0] == 0) {
			$bool = $this->update(
				'INSERT INTO journal_settings
					(journal_id, setting_name, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?)',
				array($journalId, $name, $value, $type)
			);
		} else {
			$bool = $this->update(
				'UPDATE journal_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE journal_id = ? AND setting_name = ?',
				array($value, $type, $journalId, $name)
			);
		}
				                              
		if ($bool) {
			$this->journalSettings[$journalId][$name] = $type == 'object' ? unserialize($value) : $value;
		}
	
		return $bool;
	}
	
	/**
	 * Delete a journal setting.
	 * @param $journalId int
	 * @param $name string
	 */
	function deleteSetting($journalId, $name) {
		$bool =	$this->update(
					'DELETE FROM journal_settings WHERE journal_id = ? AND setting_name = ?',
					array($journalId, $name)
					);
					
		if ($bool) {
			unset($this->journalSettings[$journalId][$name]);
		}
		
		return $bool;	
	}
	
	/**
	 * Delete all settings for a journal.
	 * @param $journalId int
	 */
	function deleteSettingsByJournal($journalId) {
		$bool = $this->update(
					'DELETE FROM journal_settings WHERE journal_id = ?', $journalId
					);
					
		if ($bool) {
			unset($this->journalSettings[$journalId]);
		}
		
		return $bool;
	}
	
}

?>
