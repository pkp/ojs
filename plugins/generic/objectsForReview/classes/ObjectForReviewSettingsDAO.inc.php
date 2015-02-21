<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewSettingsDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewSettingsDAO
 * @ingroup submission
 *
 * @brief Operations for retrieving and modifying object for review settings.
 */


class ObjectForReviewSettingsDAO extends DAO {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ObjectForReviewSettingsDAO($parentPluginName){
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Retrieve object for review setting value.
	 * @param $objectId int
	 * @param $metadataId int
	 */
	function &getSetting($objectId, $metadataId) {
		$params = array((int) $objectId, (int) $metadataId);
		$sql = 'SELECT * FROM object_for_review_settings WHERE object_id = ? AND review_object_metadata_id = ?';
		$result =& $this->retrieve($sql, $params);

		$setting = null;
		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$setting[$row['review_object_metadata_id']] = $value;
			$result->MoveNext();
		}
		$result->Close();
		return $setting;
	}

	/**
	 * Retrieve all settings for the object for review.
	 * @param $objectId int
	 * @return array
	 */
	function &getSettings($objectId) {
		$result =& $this->retrieve(
			'SELECT review_object_metadata_id, setting_value, setting_type FROM object_for_review_settings WHERE object_id = ?', (int) $objectId
		);

		$objectForReviewSettings = array();
		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$objectForReviewSettings[$row['review_object_metadata_id']] = $value;
			$result->MoveNext();
		}
		$result->Close();
		return $objectForReviewSettings;
	}

	/**
	 * Add/update an object for review setting.
	 * @param $objectId int
	 * @param $metadataId int
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @return boolean
	 */
	function updateSetting($objectId, $metadataId, $value, $type = null) {
		$keyFields = array('object_id', 'review_object_metadata_id');
		$value = $this->convertToDB($value, $type);
		$this->replace('object_for_review_settings',
			array(
				'object_id' => (int) $objectId,
				'review_object_metadata_id' => (int) $metadataId,
				'setting_value' => $value,
				'setting_type' => $type
			),
			$keyFields
		);
		return true;
	}

	/**
	 * Delete an object for review setting.
	 * @param $objectId int
	 * @param $metadataId int
	 */
	function deleteSetting($objectId, $metadataId) {
		$params = array((int) $objectId, (int) $metadataId);
		$sql = 'DELETE FROM object_for_review_settings WHERE object_id = ? AND review_object_metadata_id = ?';
		return $this->update($sql, $params);
	}

	/**
	 * Delete all settings for an object for review.
	 * @param $objectId int
	 */
	function deleteSettings($objectId) {
		return $this->update(
			'DELETE FROM object_for_review_settings WHERE object_id = ?', (int) $objectId
		);
	}

	/**
	 * Delete settings by review object metadata ID
	 * to be called only when deleting a review object metadata.
	 * @param $reviewObjectMetadataId int
	 */
	function deleteByReviewObjectMetadataId($reviewObjectMetadataId) {
		return $this->update(
			'DELETE FROM object_for_review_settings WHERE review_object_metadata_id = ?', (int) $reviewObjectMetadataId
		);
	}

}

?>
