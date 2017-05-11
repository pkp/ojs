<?php

/**
 * @file controllers/grid/announcements/form/AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeForm
 * @ingroup controllers_grid_announcements_form
 * @see AnnouncementType
 *
 * @brief Form for manager to create/edit announcement types.
 */


import('lib.pkp.classes.form.Form');

class AnnouncementTypeForm extends Form {
	/** @var int Context ID */
	var $contextId;

	/** @var typeId int the ID of the announcement type being edited */
	var $typeId;

	/**
	 * Constructor
	 * @param $contextId int Context ID
	 * @param $typeId int leave as default for new announcement type
	 */
	function __construct($contextId, $typeId = null) {
		$this->typeId = isset($typeId) ? (int) $typeId : null;
		$this->contextId = $contextId;

		parent::__construct('manager/announcement/announcementTypeForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.announcementTypes.form.typeNameRequired'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		return $announcementTypeDao->getLocaleFieldNames();
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('typeId', $this->typeId);
		return parent::fetch($request, 'controllers/grid/announcements/form/announcementTypeForm.tpl');
	}

	/**
	 * Initialize form data from current announcement type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
			$announcementType = $announcementTypeDao->getById($this->typeId);

			if ($announcementType != null) {
				$this->_data = array(
					'name' => $announcementType->getName(null) // Localized
				);

			} else {
				$this->typeId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name'));

	}

	/**
	 * Save announcement type.
	 */
	function execute() {
		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');

		if (isset($this->typeId)) {
			$announcementType = $announcementTypeDao->getById($this->typeId);
		}

		if (!isset($announcementType)) {
			$announcementType = $announcementTypeDao->newDataObject();
		}

		$announcementType->setAssocType(Application::getContextAssocType());
		$announcementType->setAssocId($this->contextId);
		$announcementType->setName($this->getData('name'), null); // Localized

		// Update or insert announcement type
		if ($announcementType->getId() != null) {
			$announcementTypeDao->updateObject($announcementType);
		} else {
			$announcementTypeDao->insertObject($announcementType);
		}
	}
}

?>
