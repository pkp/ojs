<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReview.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReview
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewDAO
 *
 * @brief Basic class describing an object for review.
 */


class ObjectForReview extends DataObject {
	/**
	 * Constructor.
	 */
	function ObjectForReview() {
		parent::DataObject();
	}

	/**
	 * Return string of person names, separated by the specified token
	 * @param $lastOnly boolean return the list of lastnames only (default false)
	 * @param $separator string separator for names (default comma+space)
	 * @return string
	 */
	function getPersonString($lastOnly = false, $separator = ', ') {
		$str = '';
		$persons = $this->getPersons();
		foreach ($persons as $person) {
			if (!empty($str)) {
				$str .= $separator;
			}
			$str .= $lastOnly ? $person->getLastName() : $person->getFullName();
		}
		return $str;
	}

	/**
	 * Get all persons of this object for review.
	 * @return array of ObjectForReviewPerson
	 */
	function &getPersons() {
		$ofrPersonDao =& DAORegistry::getDAO('ObjectForReviewPersonDAO');
		return $ofrPersonDao->getByObjectForReview($this->getId());
	}

	/**
	 * Get editor ID.
	 * @return int
	 */
	function getEditorId() {
		return $this->getData('editorId');
	}

	/**
	 * Set editor ID.
	 * @param $editor int
	 */
	function setEditorId($editorId) {
		return $this->setData('editorId', $editorId);
	}

	/**
	 * Get editor assigned to the object for review.
	 * @return User
	 */
	function &getEditor() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getById($this->getData('editorId'));
	}

	/**
	 * Get editor's initials assigned to the object for review.
	 * @return string
	 */
	function getEditorInitials() {
		$editor =& $this->getEditor();
		if ($editor) {
			$initials = $editor->getInitials();
			if (!empty($initials)) {
				return $initials;
			} else {
				return substr($editor->getFirstName(), 0, 1) . substr($editor->getLastName(), 0, 1);
			}
		}
	}

	/**
	 * Get review object type ID.
	 * @return int
	 */
	function getReviewObjectTypeId() {
		return $this->getData('reviewObjectTypeId');
	}

	/**
	 * Set review object type ID.
	 * @param $reviewObjectTypeId int
	 */
	function setReviewObjectTypeId($reviewObjectTypeId) {
		return $this->setData('reviewObjectTypeId', $reviewObjectTypeId);
	}

	/**
	 * Get review object type.
	 * @return ReviewObjectType
	 */
	function &getReviewObjectType() {
		$reviewObjectTypeDao =& DAORegistry::getDAO('ReviewObjectTypeDAO');
		return $reviewObjectTypeDao->getById($this->getData('reviewObjectTypeId'));
	}

	/**
	 * Get context ID.
	 * @return int
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set context ID.
	 * @param $contextId int
	 */
	function setContextId($contextId) {
		return $this->setData('contextId', $contextId);
	}

	/**
	 * Get available status of the object for review.
	 * @return int
	 */
	function getAvailable() {
		return $this->getData('available');
	}

	/**
	 * Set available status of the object for review.
	 * @param $available int
	 */
	function setAvailable($available) {
		return $this->setData('available', $available);
	}

	/**
	 * Get object for review available status locale key.
	 * @return string
	 */
	function getStatusString() {
		if ($this->getData('available')) {
			return 'plugins.generic.objectsForReview.editor.objectForReview.status.available';
		} else {
			return 'plugins.generic.objectsForReview.editor.objectForReview.status.notAvailable';
		}
	}

	/**
	 * Get dateCreated.
	 * @return string
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set dateCreated.
	 * @param $dateCreated string
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated', $dateCreated);
	}

	/**
	 * Get notes for the object for review.
	 * @return string
	 */
	function getNotes() {
		return $this->getData('notes');
	}

	/**
	 * Set notes for the object for review.
	 * @param $notes string
	 */
	function setNotes($notes) {
		return $this->setData('notes', $notes);
	}

	//
	// Get settings
	//
	/**
	 * Get title.
	 * @return string
	 */
	function getTitle() {
		return $this->getSettingByKey('title');
	}

	/**
	 * Get cover page.
	 * @return string
	 */
	function getCoverPage() {
		return $this->getSettingByKey('coverPage');
	}

	/**
	 * Get languages.
	 * @return string
	 */
	function getLanguages() {
		$languageDao =& DAORegistry::getDAO('LanguageDAO');
		$languageCodes = $this->getSettingByKey('language');
		$languages = array();
		if (isset($languageCodes)) foreach ($languageCodes as $languageCode) {
			$language =& $languageDao->getLanguageByCode($languageCode);
			if ($language) $languages[] = $language->getName();
			unset($language);
		}
		return implode(';', $languages);
	}

	/**
	 * Get info if there is a copy of the object for review.
	 * @return int
	 */
	function getCopy() {
		return $this->getSettingByKey('copy');
	}

	/**
	 * Retrieve array of object for review settings.
	 * @return array
	 */
	function &getSettings() {
		$ofrSettingsDao =& DAORegistry::getDAO('ObjectForReviewSettingsDAO');
		$settings =& $ofrSettingsDao->getSettings($this->getId());
		return $settings;
	}

	/**
	 * Retrieve object for review setting value by reivew object metadata id.
	 * @param $reviewObjectMetadataId int
	 * @return mixed
	 */
	function &getSetting($reviewObjectMetadataId) {
		$ofrSettingsDao =& DAORegistry::getDAO('ObjectForReviewSettingsDAO');
		$setting =& $ofrSettingsDao->getSetting($this->getId(), $reviewObjectMetadataId);
		return $setting[$reviewObjectMetadataId];
	}

	/**
	 * Update object for review setting value.
	 * @param $reviewObjectMetadataId int
	 * @param $value mixed
	 * @param $type string (optional)
	 */
	function updateSetting($reviewObjectMetadataId, $value, $type = null) {
		$ofrSettingsDao =& DAORegistry::getDAO('ObjectForReviewSettingsDAO');
		return $ofrSettingsDao->updateSetting($this->getId(), $reviewObjectMetadataId, $value, $type);
	}

	/**
	 * Retrieve metadata ID by key.
	 * @param $key string
	 * @return int
	 */
	function getMetadataId($key) {
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		return $reviewObjectMetadataDao->getMetadataId($this->getReviewObjectTypeId(), $key);
	}

	/**
	 * Retrieve object for review setting value by reivew object metadata key.
	 * @param $key string
	 * @return mixed
	 */
	function &getSettingByKey($key) {
		$metadataId = $this->getMetadataId($key);
		return $this->getSetting($metadataId);
	}

}

?>
