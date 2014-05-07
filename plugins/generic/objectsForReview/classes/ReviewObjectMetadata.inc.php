<?php

/**
 * @file plugins/generic/objectsForReview/classes/ReviewObjectMetadata.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewObjectMetadata
 * @ingroup plugins_generic_objectsForReview
 * @see ReviewObjectMetadataDAO
 *
 * @brief Basic class describing a review object metadata.
 *
 */

define('REVIEW_OBJECT_METADATA_TYPE_SMALL_TEXT_FIELD',		0x000001);
define('REVIEW_OBJECT_METADATA_TYPE_TEXT_FIELD',			0x000002);
define('REVIEW_OBJECT_METADATA_TYPE_TEXTAREA',	    		0x000003);
define('REVIEW_OBJECT_METADATA_TYPE_CHECKBOXES',			0x000004);
define('REVIEW_OBJECT_METADATA_TYPE_RADIO_BUTTONS', 		0x000005);
define('REVIEW_OBJECT_METADATA_TYPE_DROP_DOWN_BOX', 		0x000006);
define('REVIEW_OBJECT_METADATA_TYPE_ROLE_DROP_DOWN_BOX', 	0x000007);
define('REVIEW_OBJECT_METADATA_TYPE_LANG_DROP_DOWN_BOX', 	0x000008);
define('REVIEW_OBJECT_METADATA_TYPE_COVERPAGE', 			0x000009);

define('REVIEW_OBJECT_METADATA_KEY_TITLE',		'title');
define('REVIEW_OBJECT_METADATA_KEY_ROLE',		'role');
define('REVIEW_OBJECT_METADATA_KEY_DATE',		'date');
define('REVIEW_OBJECT_METADATA_KEY_LANG',		'language');
define('REVIEW_OBJECT_METADATA_KEY_SHORTTITLE',	'shortTitle');
define('REVIEW_OBJECT_METADATA_KEY_URL',		'url');
define('REVIEW_OBJECT_METADATA_KEY_RIGHTS',		'rights');
define('REVIEW_OBJECT_METADATA_KEY_ABSTRACT',	'abstract');
define('REVIEW_OBJECT_METADATA_KEY_COPY',		'copy');
define('REVIEW_OBJECT_METADATA_KEY_COVERPAGE',	'coverPage');


class ReviewObjectMetadata extends DataObject {
	/**
	 * Constructor.
	 */
	function ReviewObjectMetadata() {
		parent::DataObject();
	}

	/**
	 * Get localized name.
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get localized list of possible options.
	 * @return array
	 */
	function getLocalizedPossibleOptions() {
		return $this->getLocalizedData('possibleOptions');
	}

	//
	// Get/set methods
	//
	/**
	 * Get review object type ID.
	 * @return int
	 */
	function getReviewObjectTypeId() {
		return $this->getData('reviewObjectTypeId');
	}

	/**
	 * Set review object type ID.
	 * @param $typeId int
	 */
	function setReviewObjectTypeId($reviewObjectTypeId) {
		return $this->setData('reviewObjectTypeId', $reviewObjectTypeId);
	}

	/**
	 * Get sequence.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Get type.
	 * @return string
	 */
	function getMetadataType() {
		return $this->getData('metadataType');
	}

	/**
	 * Set type.
	 * @param $metadataType string
	 */
	function setMetadataType($metadataType) {
		return $this->setData('metadataType', $metadataType);
	}

	/**
	 * Get required flag.
	 * @return boolean
	 */
	function getRequired() {
		return $this->getData('required');
	}

	/**
	 * Set required flag.
	 * @param $required boolean
	 */
	function setRequired($required) {
		return $this->setData('required', $required);
	}

	/**
	 * Get display flag.
	 * @return boolean
	 */
	function getDisplay() {
		return $this->getData('display');
	}

	/**
	 * Set display flag.
	 * @param $display boolean
	 */
	function setDisplay($display) {
		return $this->setData('display', $display);
	}

	/**
	 * Get key.
	 * @return string
	 */
	function getKey() {
		return $this->getData('key');
	}

	/**
	 * Set key.
	 * @param $key string
	 */
	function setKey($key) {
		return $this->setData('key', $key);
	}

	/**
	 * Get name.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set name.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		return $this->setData('name', $name, $locale);
	}

	/**
	 * Get possible otions.
	 * @param $locale string
	 * @return string
	 */
	function getPossibleOptions($locale) {
		return $this->getData('possibleOptions', $locale);
	}

	/**
	 * Set possible options.
	 * @param $possibleOptions string
	 * @param $locale string
	 */
	function setPossibleOptions($possibleOptions, $locale) {
		return $this->setData('possibleOptions', $possibleOptions, $locale);
	}

	/**
	 * Get an associative array matching metadata type codes with locale strings.
	 * (Includes default '' => "Choose One" string.)
	 * @return array metadataType => localeString
	 */
	function &getMetadataFormTypeOptions() {
		static $metadataTypeOptions = array(
			'' => 'plugins.generic.objectsForReview.editor.objectMetadata.type.chooseType',
			REVIEW_OBJECT_METADATA_TYPE_SMALL_TEXT_FIELD => 'plugins.generic.objectsForReview.editor.objectMetadata.type.smalltextfield',
			REVIEW_OBJECT_METADATA_TYPE_TEXT_FIELD => 'plugins.generic.objectsForReview.editor.objectMetadata.type.textfield',
			REVIEW_OBJECT_METADATA_TYPE_TEXTAREA => 'plugins.generic.objectsForReview.editor.objectMetadata.type.textarea',
			REVIEW_OBJECT_METADATA_TYPE_CHECKBOXES => 'plugins.generic.objectsForReview.editor.objectMetadata.type.checkboxes',
			REVIEW_OBJECT_METADATA_TYPE_RADIO_BUTTONS => 'plugins.generic.objectsForReview.editor.objectMetadata.type.radiobuttons',
			REVIEW_OBJECT_METADATA_TYPE_DROP_DOWN_BOX => 'plugins.generic.objectsForReview.editor.objectMetadata.type.dropdownbox'
		);
		return $metadataTypeOptions;
	}

	/**
	 * Get metadata DTD types.
	 * @return array
	 */
	function &getMetadataDTDTypes() {
		static $metadataDTDTypes =  array(
			'smallTextField' => REVIEW_OBJECT_METADATA_TYPE_SMALL_TEXT_FIELD,
			'singleLineTextBox' => REVIEW_OBJECT_METADATA_TYPE_TEXT_FIELD,
			'extendedTextBox' => REVIEW_OBJECT_METADATA_TYPE_TEXTAREA,
			'checkBoxes' => REVIEW_OBJECT_METADATA_TYPE_CHECKBOXES,
			'radioButtons' => REVIEW_OBJECT_METADATA_TYPE_RADIO_BUTTONS,
			'dropDownBox' => REVIEW_OBJECT_METADATA_TYPE_DROP_DOWN_BOX,
			'roleDropDownBox' => REVIEW_OBJECT_METADATA_TYPE_ROLE_DROP_DOWN_BOX,
			'languageDropDownBox' => REVIEW_OBJECT_METADATA_TYPE_LANG_DROP_DOWN_BOX,
			'coverPage' => REVIEW_OBJECT_METADATA_TYPE_COVERPAGE
		);
		return $metadataDTDTypes;
	}

	/**
	 * Get array of all multiple options metadata types.
	 * @return array multiple options types
	 */
	function &getMultipleOptionsTypes() {
		static $multipleOptionsTypes = array(
			REVIEW_OBJECT_METADATA_TYPE_CHECKBOXES,
			REVIEW_OBJECT_METADATA_TYPE_RADIO_BUTTONS,
			REVIEW_OBJECT_METADATA_TYPE_DROP_DOWN_BOX,
			REVIEW_OBJECT_METADATA_TYPE_ROLE_DROP_DOWN_BOX
		);
		return $multipleOptionsTypes;
	}

	/**
	 * Get array of all common metadata keys.
	 * @return array common metadata keys
	 */
	function &getCommonMetadataKeys() {
		static $commonMetadataKeys = array(
			REVIEW_OBJECT_METADATA_KEY_TITLE,
			REVIEW_OBJECT_METADATA_KEY_ROLE,
			REVIEW_OBJECT_METADATA_KEY_DATE,
			REVIEW_OBJECT_METADATA_KEY_LANG,
			REVIEW_OBJECT_METADATA_KEY_SHORTTITLE,
			REVIEW_OBJECT_METADATA_KEY_URL,
			REVIEW_OBJECT_METADATA_KEY_RIGHTS,
			REVIEW_OBJECT_METADATA_KEY_ABSTRACT,
			REVIEW_OBJECT_METADATA_KEY_COPY,
			REVIEW_OBJECT_METADATA_KEY_COVERPAGE
		);
		return $commonMetadataKeys;
	}

	/**
	 * Check if this is a common metadata.
	 * @return boolean
	 */
	function isCommon() {
		return in_array($this->getKey(), $this->getCommonMetadataKeys());
	}

	/**
	 * Check if there is a key for this metadata,
	 * i.e. if the metadata is predefined.
	 * @return boolean
	 */
	function keyExists() {
		return $this->getKey() != '';
	}

	/**
	 * Get localised content of the possible option.
	 * @param $order int
	 * @return string
	 */
	function getLocalizedPossibleOptionContent($order) {
		$possibleOptions = $this->getLocalizedData('possibleOptions');
		foreach ($possibleOptions as $option) {
			if ($option['order'] == $order) {
				return $option['content'];
			}
		}
		return null;
	}

}

?>
