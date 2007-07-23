<?php

/**
 * @file Captcha.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package captcha
 * @class Captcha
 *
 * Class for Captcha verifiers.
 *
 * $Id$
 */
 
class Captcha extends DataObject {
 
	/**
	 * Constructor.
	 */
	function Captcha() {
		parent::DataObject();
	}
	
	/**
	 * get article captcha id
	 * @return int
	 */
	function getCaptchaId() {
		return $this->getData('captchaId');
	}
	 
	/**
	 * set captcha id
	 * @param $captchaId int
	 */
	function setCaptchaId($captchaId) {
		return $this->setData('captchaId', $captchaId);
	}
	
	/**
	 * get session id
	 * @return int
	 */
	function getSessionId() {
		return $this->getData('sessionId');
	}
	 
	/**
	 * set session id
	 * @param $sessionId int
	 */
	function setSessionId($sessionId) {
		return $this->setData('sessionId', $sessionId);
	}
	
	/**
	 * get value
	 * @return string
	 */
	function getValue() {
		return $this->getData('value');
	}
	 
	/**
	 * set value
	 * @param $value string
	 */
	function setValue($value) {
		return $this->setData('value', $value);
	}

	/**
	 * get poster name
	 */
	function getPosterName() {
		return $this->getData('posterName');
	}

	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated', $dateCreated);
	}
	
 	/**
	 * get date created
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}
}
 
?>
