<?php

/**
 * Session.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package session
 *
 * Session class.
 * Maintains user state information from one request to the next.
 *
 * $Id$
 */

class Session extends DataObject  {

	/** The User object associated with this session */
	var $user;
	
	/**
	 * Constructor.
	 */
	function Session() {
		parent::DataObject();
	}
	
	/**
	 * Get a session variable's value.
	 * @param $key string
	 * @return mixed
	 */
	function getSessionVar($key) {
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}
	
	/**
	 * Get a session variable's value.
	 * @param $key string
	 * @param $value mixed
	 * @return mixed
	 */
	function setSessionVar($key, $value) {
		$_SESSION[$key] = $value;
		return $value;
	}
	
	/**
	 * Unset (delete) a session variable.
	 * @param $key string
	 */
	function unsetSessionVar($key) {
		if (isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
		
		if (session_is_registered($key)) {
			session_unregister($key);
		}
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get session ID.
	 * @return string
	 */
	function getId() {
		return $this->getData('id');
	}
	
	/**
	 * Set session ID.
	 * @param $id string
	 */
	function setId($id) {
		return $this->setData('id', $id);
	}
	
	/**
	 * Get user ID (0 if anonymous user).
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set user ID.
	 * @param $userId int
	 */
	function setUserId($userId) {
		if (!isset($userId) || empty($userId)) {
			$this->user = null;
			$userId = null;
			
		} else if ($userId != $this->getData('userId')) {
			$userDao = &DAORegistry::getDAO('UserDAO');
			$this->user = &$userDao->getUser($userId);
			if (!isset($this->user)) {
				$userId = null;
			}
		}
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Get IP address.
	 * @return string
	 */
	function getIpAddress() {
		return $this->getData('ipAddress');
	}
	
	/**
	 * Set IP address.
	 * @param $ipAddress string
	 */
	function setIpAddress($ipAddress) {
		return $this->setData('ipAddress', $ipAddress);
	}
	
	/**
	 * Get user agent.
	 * @return string
	 */
	function getUserAgent() {
		return $this->getData('userAgent');
	}
	
	/**
	 * Set user agent.
	 * @param $userAgent string
	 */
	function setUserAgent($userAgent) {
		return $this->setData('userAgent', $userAgent);
	}
	
	/**
	 * Get time (in seconds) since session was created.
	 * @return int
	 */
	function getSecondsCreated() {
		return $this->getData('created');
	}
	
	/**
	 * Set time (in seconds) since session was created.
	 * @param $created int
	 */
	function setSecondsCreated($created) {
		return $this->setData('created', $created);
	}
	
	/**
	 * Get time (in seconds) since session was last used.
	 * @return int
	 */
	function getSecondsLastUsed() {
		return $this->getData('lastUsed');
	}
	
	/**
	 * Set time (in seconds) since session was last used.
	 * @param $lastUsed int
	 */
	function setSecondsLastUsed($lastUsed) {
		return $this->setData('lastUsed', $lastUsed);
	}
	
	/**
	 * Check if session is to be saved across browser sessions.
	 * @return boolean
	 */
	function getRemember() {
		return $this->getData('remember');
	}
	
	/**
	 * Set whether session is to be saved across browser sessions.
	 * @param $remember boolean
	 */
	function setRemember($remember) {
		return $this->setData('remember', $remember);
	}
	
	/**
	 * Get all session parameters.
	 * @return array
	 */
	function getSessionData() {
		return $this->getData('data');
	}
	
	/**
	 * Set session parameters.
	 * @param $data array
	 */
	function setSessionData($data) {
		return $this->setData('data', $data);
	}
	
	/**
	 * Get user associated with this session (null if anonymous user).
	 * @return User
	 */
	function &getUser() {
		return $this->user;
	}
	
}

?>
