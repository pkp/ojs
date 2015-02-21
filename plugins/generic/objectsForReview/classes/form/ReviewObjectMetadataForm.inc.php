<?php

/**
 * @file plugins/generic/objectsForReview/classes/form/ReviewObjectMetadataForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewObjectMetadataForm
 * @ingroup plugins_generic_objectsForReview
 * @see ReviewObjectMetadata
 *
 * @brief Form for creating and modifying review object metadata.
 *
 */

import('lib.pkp.classes.form.Form');

class ReviewObjectMetadataForm extends Form {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	/** @var int ID of the ReviewObjectType being edited */
	var $reviewObjectTypeId;

	/** @var object ReviewObjectMetadata being edited */
	var $reviewObjectMetadata;

	/**
	 * Constructor.
	 * @param $parentPluginName sting
	 * @param $reviewObjectTypeId int
	 * @param $metadataId int (optional)
	 */
	function ReviewObjectMetadataForm($parentPluginName, $reviewObjectTypeId, $metadataId = null) {
		$this->parentPluginName = $parentPluginName;
		$this->reviewObjectTypeId = (int) $reviewObjectTypeId;

		$ofrPlugin =& PluginRegistry::getPlugin('generic', $parentPluginName);
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$ofrPlugin->import('classes.ReviewObjectMetadata');
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		if (!empty($metadataId)) {
			$this->reviewObjectMetadata =& $reviewObjectMetadataDao->getById((int) $metadataId, $this->reviewObjectTypeId);
		} else {
			$this->reviewObjectMetadata = null;
		}
		parent::Form($ofrPlugin->getTemplatePath() . 'editor/reviewObjectMetadataForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'plugins.generic.objectsForReview.editor.objectMetadata.form.nameRequired'));
		$this->addCheck(new FormValidator($this, 'metadataType', 'required', 'plugins.generic.objectsForReview.editor.objectMetadata.form.typeRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * @see Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		return $reviewObjectMetadataDao->getLocaleFieldNames();
	}

	/**
	 * @see Form::display()
	 */
	function display($request) {
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('reviewObjectMetadata', $this->reviewObjectMetadata);
		$templateMgr->assign('reviewObjectTypeId', $this->reviewObjectTypeId);

		$ofrPlugin =& PluginRegistry::getPlugin('generic', OBJECTS_FOR_REVIEW_PLUGIN_NAME);
		$ofrPlugin->import('classes.ReviewObjectMetadata');
		$templateMgr->assign('multipleOptionsTypes', ReviewObjectMetadata::getMultipleOptionsTypes());
		// in order to be able to search for an element in the array in the javascript function 'togglePossibleResponses':
		$templateMgr->assign('multipleOptionsTypesString', ';'.implode(';', ReviewObjectMetadata::getMultipleOptionsTypes()).';');
		$templateMgr->assign('metadataTypeOptions', ReviewObjectMetadata::getMetadataFormTypeOptions());
		parent::display($request);
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		if ($this->reviewObjectMetadata != null) {
			$reviewObjectMetadata =& $this->reviewObjectMetadata;
			$this->_data = array(
				'name' => $reviewObjectMetadata->getName(null), // Localized
				'required' => $reviewObjectMetadata->getRequired(),
				'display' => $reviewObjectMetadata->getDisplay(),
				'metadataType' => $reviewObjectMetadata->getMetadataType(),
				'possibleOptions' => $reviewObjectMetadata->getPossibleOptions(null) //Localized
			);
		}
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'required', 'display', 'metadataType', 'possibleOptions'));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$ofrPlugin->import('classes.ReviewObjectMetadata');
		$reviewObjectMetadataDao =& DAORegistry::getDAO('ReviewObjectMetadataDAO');
		if ($this->reviewObjectMetadata == null) {
			$reviewObjectMetadata = $reviewObjectMetadataDao->newDataObject();
			$reviewObjectMetadata->setReviewObjectTypeId($this->reviewObjectTypeId);
			$reviewObjectMetadata->setSequence(REALLY_BIG_NUMBER);
		} else {
			$reviewObjectMetadata =& $this->reviewObjectMetadata;
		}
		$reviewObjectMetadata->setName($this->getData('name'), null); // Localized
		$reviewObjectMetadata->setRequired($this->getData('required') ? 1 : 0);
		$reviewObjectMetadata->setDisplay($this->getData('display') ? 1 : 0);
		$reviewObjectMetadata->setMetadataType($this->getData('metadataType'));

		if (in_array($this->getData('metadataType'), ReviewObjectMetadata::getMultipleOptionsTypes())) {
			$reviewObjectMetadata->setPossibleOptions($this->getData('possibleOptions'), null); // Localized
		} else {
			$reviewObjectMetadata->setPossibleOptions(null, null);
		}

		if ($reviewObjectMetadata->getId() != null) {
			$reviewObjectMetadataDao->deleteSetting($reviewObjectMetadata->getId(), 'possibleOptions');
			$reviewObjectMetadataDao->updateObject($reviewObjectMetadata);
		} else {
			$reviewObjectMetadataDao->insertObject($reviewObjectMetadata);
			$reviewObjectMetadataDao->resequence($this->reviewObjectTypeId);
		}
	}

}

?>
