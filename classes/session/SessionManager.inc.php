<?php

/**
 * SessionManager.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package session
 *
 * Class for handling sessions.
 * Implements PHP methods for a custom session storage handler (see http://php.net/session).
 *
 * $Id$
 */

class SessionManager {

	/** The DAO for accessing Session objects */
	var $sessionDao;
	
	/** The Session associated with the current request */
	var $userSession;
	
	/**
	 * Constructor.
	 * Initialize session configuration and set PHP session handlers.
	 * Attempts to rejoin a user's session if it exists, or create a new session otherwise.
	 */
	function SessionManager(&$sessionDao) {
		$this->sessionDao = &$sessionDao;
		
		// Configure PHP session parameters
		ini_set('session.use_trans_sid', 0);
		ini_set('session.save_handler', 'user');
		ini_set('session.serialize_handler', 'php');
		ini_set('session.use_cookies', 1);
		ini_set('session.name', 'OJSSID'); // Cookie name
		ini_set('session.cookie_lifetime', 0);
		ini_set('session.cookie_path', '/');
		ini_set('session.gc_probability', 1);
		ini_set('session.gc_maxlifetime', 60 * 60);
		ini_set('session.auto_start', 1);
		
		session_set_save_handler(
			array(&$this, 'open'),
			array(&$this, 'close'),
			array(&$this, 'read'),
			array(&$this, 'write'),
			array(&$this, 'destroy'),
			array(&$this, 'gc')
		);
		
		// Initialize the session
		session_start();
		$session_id = session_id();
		
		$ip = $this->ipAddr();
		$now = time();
		
		if (!isset($this->userSession) || $this->userSession->getIpAddress() != $ip) {
			if (!isset($this->userSession)) {
				// Destroy old session
				session_destroy();
			}
			
			// Create new session
			$this->userSession = new Session();
			$this->userSession->setId($session_id);
			$this->userSession->setIpAddress($ip);
			$this->userSession->setSecondsCreated($now);
			$this->userSession->setSecondsLastUsed($now);
			$this->userSession->setSessionData('');
			
			$this->sessionDao->insertSession($this->userSession);
			
		} else {
			if ($this->userSession->getRemember()) {
				// Update session timestamp for remembered sessions so it doesn't expire in the middle of a browser session
				if (Config::getVar('general', 'session_lifetime') > 0) {
					$this->updateSessionLifetime(time() + Config::getVar('general', 'session_lifetime') * 86400);
				} else {
					$this->userSession->setRemember(false);
					$this->updateSessionLifetime(0);
				}
			}
			
			// Update existing session's timestamp
			$this->userSession->setSecondsLastUsed($now);
			$this->sessionDao->updateSession($this->userSession);
		}
	}

	/**
	 * Return an instance of the session manager.
	 * @return SessionManager
	 */
	function &getManager() {
		static $instance;
		
		if (!isset($instance)) {
			$instance = new SessionManager(DAORegistry::getDAO('SessionDAO'));
		}
		return $instance;
	}

	/**
	 * Get the session associated with the current request.
	 * @return Session
	 */
	function &getUserSession() {
		return $this->userSession;
	}
	
	/**
	 * Open a session.
	 * Does nothing; only here to satisfy PHP session handler requirements.
	 * @return boolean
	 */
	function open() {
		return true;
	}
	
	/**
	 * Close a session.
	 * Does nothing; only here to satisfy PHP session handler requirements.
	 * @return boolean
	 */
	function close() {
		return true;
	}
	
	/**
	 * Read session data from database.
	 * @param $sessionId string
	 * @return boolean
	 */
	function read($sessionId) {
		if (!isset($this->userSession)) {
			$this->userSession = &$this->sessionDao->getSession($sessionId);
			if (isset($this->userSession)) {
				$data = &$this->userSession->getSessionData();
			}
		}
		return isset($data) ? $data : '';
	}
	
	/**
	 * Save session data to database.
	 * @param $sessionId string
	 * @param $data array
	 * @return boolean
	 */
	function write($sessionId, $data) {
		if (isset($this->userSession)) {
			$this->userSession->setSessionData($data);
			return $this->sessionDao->updateSession($this->userSession);
			
		} else {
			return true;
		}
	}
	
	/**
	 * Destroy (delete) a session.
	 * @param $sessionId string
	 * @return boolean
	 */
	function destroy($sessionId) {
		return $this->sessionDao->deleteSessionById($sessionId);
	}
	
	/**
	 * Garbage collect unused session data.
	 * TODO: Use $maxlifetime instead of assuming 24 hours?
	 * @param $maxlifetime int the number of seconds after which data will be seen as "garbage" and cleaned up
	 * @return boolean
	 */
	function gc($maxlifetime) {
		return $this->sessionDao->deleteSessionByLastUsed(time() - 86400, Config::getVar('general', 'session_lifetime') <= 0 ? 0 : time() - Config::getVar('general', 'session_lifetime') * 86400);
	}

	/**
	 * Get the current requester's IP address.
	 * @return string
	 */
	function ipAddr() {
		$ipaddr = $_SERVER['REMOTE_ADDR'];
		if (empty($ipaddr)) {
			$ipaddr = getenv('REMOTE_ADDR');
		}
		return $ipaddr;
	}
	
	/**
	 * Change the lifetime of the current session cookie.
	 * @param $expireTime int new expiration time in seconds (0 = current session)
	 * @return boolean
	 */
	function updateSessionLifetime($expireTime = 0) {
		return setcookie(session_name(), session_id(), $expireTime, ini_get('session.cookie_path'));
	}
	
}

?>
