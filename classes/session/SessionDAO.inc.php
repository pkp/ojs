<?php

/**
 * SessionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package session
 *
 * Class for Session DAO.
 * Operations for retrieving and modifying Session objects.
 *
 * $Id$
 */

class SessionDAO extends DAO {

	/**
	 * Constructor.
	 */
	function SessionDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a session by ID.
	 * @param $sessionId string
	 * @return Session
	 */
	function &getSession($sessionId) {
		$result = &$this->retrieve(
			'SELECT * FROM sessions WHERE session_id = ?', $sessionId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			$row = &$result->GetRowAssoc(false);
			
			$session = &new Session();
			$session->setId($row['session_id']);
			$session->setUserId($row['user_id']);
			$session->setIpAddress($row['ip_address']);
			$session->setSecondsCreated($row['created']);
			$session->setSecondsLastUsed($row['last_used']);
			$session->setRemember($row['remember']);
			$session->setSessionData($row['data']);
			
			return $session;
		}
	}
	
	/**
	 * Insert a new session.
	 * @param $session Session
	 */
	function insertSession(&$session) {
		return $this->update(
			'INSERT INTO sessions
				(session_id, ip_address, created, last_used, remember, data)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$session->getId(),
				$session->getIpAddress(),
				$session->getSecondsCreated(),
				$session->getSecondsLastUsed(),
				$session->getRemember() ? 1 : 0,
				$session->getSessionData()
			)
		);
	}
	
	/**
	 * Update an existing session.
	 * @param $session Session
	 */
	function updateSession(&$session) {
		return $this->update(
			'UPDATE sessions
				SET
					user_id = ?,
					ip_address = ?,
					created = ?,
					last_used = ?,
					remember = ?,
					data = ?
				WHERE session_id = ?',
			array(
				$session->getUserId(),
				$session->getIpAddress(),
				$session->getSecondsCreated(),
				$session->getSecondsLastUsed(),
				$session->getRemember() ? 1 : 0,
				$session->getSessionData(),
				$session->getId()
			)
		);
	}
	
	/**
	 * Delete a session.
	 * @param $session Session
	 */
	function deleteSession(&$session) {
		return $this->deleteSessionById($session->getId());
	}
	
	/**
	 * Delete a session by ID.
	 * @param $sessionId string
	 */
	function deleteSessionById($sessionId) {
		return $this->update(
			'DELETE FROM sessions WHERE session_id = ?', $sessionId
		);
	}
	
	/**
	 * Delete all sessions older than the specified time.
	 * @param $lastUsed int cut-off time in seconds for not-remembered sessions
	 * @param $lastUsedRemember boolean optional, cut-off time in seconds for remembered sessions
	 */
	function deleteSessionByLastUsed($lastUsed, $lastUsedRemember = 0) {
		if ($lastUsedRemember == 0) {
			return $this->update(
				'DELETE FROM sessions WHERE (last_used < ? AND remember = 0)', $lastUsed
			);
		} else {
			return $this->update(
				'DELETE FROM sessions WHERE (last_used < ? AND remember = 0) OR (last_used < ? AND remember = 1)',
				array($lastUsed, $lastUsedRemember)
			);
		}
	}
	
}

?>
