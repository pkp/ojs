<?php

/**
 * User.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package user
 *
 * User class.
 * Basic class describing users existing in the system.
 *
 * $Id$
 */

class User extends DataObject {

	function User() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get the ID of the user.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}
	
	/**
	 * Set the ID of the user.
	 * @param $userId int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}
	
	/**
	 * Get username.
	 * @return string
	 */
	function getUsername() {
		return $this->getData('username');
	}
	
	/**
	 * Set username.
	 * @param $username string
	 */
	function setUsername($username) {
		return $this->setData('username', $username);
	}
	
	/**
	 * Get password (encrypted).
	 * @return string
	 */
	function getPassword() {
		return $this->getData('password');
	}
	
	/**
	 * Set password (assumed to be already encrypted).
	 * @param $password string
	 */
	function setPassword($password) {
		return $this->setData('password', $password);
	}
	
	/**
	 * Get first name.
	 * @return string
	 */
	function getFirstName() {
		return $this->getData('firstName');
	}
	
	/**
	 * Set first name.
	 * @param $firstName string
	 */
	function setFirstName($firstName)
	{
		return $this->setData('firstName', $firstName);
	}
	
	/**
	 * Get middle name.
	 * @return string
	 */
	function getMiddleName() {
		return $this->getData('middleName');
	}
	
	/**
	 * Set middle name.
	 * @param $middleName string
	 */
	function setMiddleName($middleName) {
		return $this->setData('middleName', $middleName);
	}
	
	/**
	 * Get initials.
	 * @return string
	 */
	function getInitials() {
		return $this->getData('initials');
	}
	
	/**
	 * Set initials.
	 * @param $initials string
	 */
	function setInitials($initials) {
		return $this->setData('initials', $initials);
	}
	
	/**
	 * Get last name.
	 * @return string
	 */
	function getLastName() {
		return $this->getData('lastName');
	}
	
	/**
	 * Set last name.
	 * @param $lastName string
	 */
	function setLastName($lastName) {
		return $this->setData('lastName', $lastName);
	}
	
	/**
	 * Get affiliation (position, institution, etc.).
	 * @return string
	 */
	function getAffiliation() {
		return $this->getData('affiliation');
	}
	
	/**
	 * Set affiliation.
	 * @param $affiliation string
	 */
	function setAffiliation($affiliation) {
		return $this->setData('affiliation', $affiliation);
	}
	
	/**
	 * Get email address.
	 * @return string
	 */
	function getEmail() {
		return $this->getData('email');
	}
	
	/**
	 * Set email address.
	 * @param $email string
	 */
	function setEmail($email) {
		return $this->setData('email', $email);
	}
	
	/**
	 * Get phone number.
	 * @return string
	 */
	function getPhone() {
		return $this->getData('phone');
	}
	
	/**
	 * Set phone number.
	 * @param $phone string
	 */
	function setPhone($phone) {
		return $this->setData('phone', $phone);
	}
	
	/**
	 * Get fax number.
	 * @return string
	 */
	function getFax() {
		return $this->getData('fax');
	}
	
	/**
	 * Set fax number.
	 * @param $fax string
	 */
	function setFax($fax) {
		return $this->setData('fax', $fax);
	}
	
	/**
	 * Get mailing address.
	 * @return string
	 */
	function getMailingAddress() {
		return $this->getData('mailingAddress');
	}
	
	/**
	 * Set mailing address.
	 * @param $mailingAddress string
	 */
	function setMailingAddress($mailingAddress) {
		return $this->setData('mailingAddress', $mailingAddress);
	}
	
	/**
	 * Get user biography.
	 * @return string
	 */
	function getBiography() {
		return $this->getData('biography');
	}
	
	/**
	 * Set user biography.
	 * @param $biography string
	 */
	function setBiography($biography) {
		return $this->setData('biography', $biography);
	}
	
	/**
	 * Get user reviewing interests.
	 * @return string
	 */
	function getInterests() {
		return $this->getData('interests');
	}
	
	/**
	 * Set user reviewing interests.
	 * @param $interests string
	 */
	function setInterests($interests) {
		return $this->setData('interests', $interests);
	}
	
	/**
	 * Get user's working languages.
	 * @return array
	 */
	function getLocales() {
		$locales = $this->getData('locales');
		return isset($locales) ? $locales : array();
	}
	
	/**
	 * Set user's working languages.
	 * @param $locales array
	 */
	function setLocales($locales) {
		return $this->setData('locales', $locales);
	}
	
	/**
	 * Get date user registered with the site.
	 * @return datestamp (YYYY-MM-DD HH:MM:SS)
	 */
	function getDateRegistered() {
		return $this->getData('dateRegistered');
	}
	
	/**
	 * Set date user registered with the site.
	 * @param $dateRegistered datestamp (YYYY-MM-DD HH:MM:SS)
	 */
	function setDateRegistered($dateRegistered) {
		return $this->setData('dateRegistered', $dateRegistered);
	}
	
	/**
	 * Get date user last logged in to the site.
	 * @return datestamp
	 */
	function getDateLastLogin() {
		return $this->getData('dateLastLogin');
	}
	
	/**
	 * Set date user last logged in to the site.
	 * @param $dateLastLogin datestamp
	 */
	function setDateLastLogin($dateLastLogin) {
		return $this->setData('dateLastLogin', $dateLastLogin);
	}
	
	/**
	 * Check if user must change their password on their next login.
	 * @return boolean
	 */
	function getMustChangePassword() {
		return $this->getData('mustChangePassword');
	}
	
	/**
	 * Set whether or not user must change their password on their next login.
	 * @param $mustChangePassword boolean
	 */
	function setMustChangePassword($mustChangePassword) {
		return $this->setData('mustChangePassword', $mustChangePassword);
	}
	
	/**
	 * Get the user's complete name.
	 * Includes first name, middle name (if applicable), and last name.
	 * @param $lastFirst boolean return in "LastName, FirstName" format
	 * @return string
	 */
	function getFullName($lastFirst = false) {
		if ($lastFirst) {
			return $this->getData('lastName') . ', ' . $this->getData('firstName') . ($this->getData('middleName') != '' ? ' ' . $this->getData('middleName') : '');
		
		} else {
			return $this->getData('firstName') . ' ' . ($this->getData('middleName') != '' ? $this->getData('middleName') . ' ' : '') . $this->getData('lastName');
		}
	}
	
}

?>
