<?php

/**
 * @file controllers/grid/announcements/form/AnnouncementForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementForm
 * @ingroup controllers_grid_announcements
 *
 * @brief Form for managers to create/edit announcements.
 */


import('lib.pkp.classes.form.Form');

class AnnouncementForm extends Form {
	/** @var boolean */
	var $_readOnly;

	/** @var announcementId int the ID of the announcement being edited */
	var $announcementId;

	/** @var int */
	var $_contextId;

	/**
	 * Constructor
	 * @param $contextId int
	 * @param announcementId int leave as default for new announcement
	 * @param $readOnly boolean
	 */
	function __construct($contextId, $announcementId = null, $readOnly = false) {

		$this->_readOnly = $readOnly;
		$this->_contextId = $contextId;
		$this->announcementId = $announcementId?(int)$announcementId:null;
		parent::__construct('manager/announcement/announcementForm.tpl');

		// Title is provided
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.announcements.form.titleRequired'));

		// Short description is provided
		$this->addCheck(new FormValidatorLocale($this, 'descriptionShort', 'required', 'manager.announcements.form.descriptionShortRequired'));

		// Description is provided
		$this->addCheck(new FormValidatorLocale($this, 'description', 'optional', 'manager.announcements.form.descriptionRequired'));

		// If provided, announcement type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'optional', 'manager.announcements.form.typeIdValid', create_function('$typeId, $contextId', '$announcementTypeDao = DAORegistry::getDAO(\'AnnouncementTypeDAO\'); if((int)$typeId === 0) { return true; } else { return $announcementTypeDao->announcementTypeExistsByTypeId($typeId, Application::getContextAssocType(), $contextId);}'), array($contextId)));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}


	//
	// Getters and setters.
	//
	/**
	 * Return if this form is read only or not.
	 */
	function isReadOnly() {
		return $this->_readOnly;
	}

	/**
	 * Get the current context id.
	 * @return int
	 */
	function getContextId() {
		return $this->_contextId;
	}


	//
	// Extended methods from Form.
	//
	/**
	 * Get the list of localized field names for this object
	 * @return array
	 */
	function getLocaleFieldNames() {
		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		return $announcementDao->getLocaleFieldNames();
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('readOnly', $this->isReadOnly());
		$templateMgr->assign('selectedTypeId', $this->getData('typeId'));

		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcement = $announcementDao->getById($this->announcementId);
		$templateMgr->assign('announcement', $announcement);

		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementTypeFactory = $announcementTypeDao->getByAssoc(Application::getContextAssocType(), $this->getContextId());

		$announcementTypeOptions = array();
		if (!$announcementTypeFactory->wasEmpty()) {
			$announcementTypeOptions = array(0 => __('common.none'));
		}
		while ($announcementType = $announcementTypeFactory->next()) {
			$announcementTypeOptions[$announcementType->getId()] = $announcementType->getLocalizedTypeName();
		}
		$templateMgr->assign('announcementTypes', $announcementTypeOptions);

		return parent::fetch($request, 'controllers/grid/announcements/form/announcementForm.tpl');
	}

	/**
	 * Initialize form data from current announcement.
	 */
	function initData() {
		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$announcement = $announcementDao->getById($this->announcementId);

		if ($announcement) {
			$this->_data = array(
				'typeId' => $announcement->getTypeId(),
				'assocType' => $announcement->getAssocType(),
				'assocId' => $announcement->getAssocId(),
				'title' => $announcement->getTitle(null), // Localized
				'descriptionShort' => $announcement->getDescriptionShort(null), // Localized
				'description' => $announcement->getDescription(null), // Localized
				'dateExpire' => $announcement->getDateExpire()
			);
		} else {
			$this->announcementId = null;
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeId', 'title', 'descriptionShort', 'description', 'dateExpireYear', 'dateExpireMonth', 'dateExpireDay', 'dateExpire'));
	}

	/**
	 * Save announcement.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');

		$announcement = $announcementDao->getById($this->announcementId);
		if (!$announcement) {
			$announcement = $announcementDao->newDataObject();
		}

		$announcement->setAssocType(Application::getContextAssocType());
		$announcement->setAssocId($this->getContextId());

		$announcement->setTitle($this->getData('title'), null); // Localized
		$announcement->setDescriptionShort($this->getData('descriptionShort'), null); // Localized
		$announcement->setDescription($this->getData('description'), null); // Localized

		if ($this->getData('typeId')) {
			$announcement->setTypeId($this->getData('typeId'));
		} else {
			$announcement->setTypeId(null);
		}

		// Give the parent class a chance to set the dateExpire.
		$dateExpireSetted = $this->setDateExpire($announcement);
		if (!$dateExpireSetted) {
			if ($this->getData('dateExpireYear') != null) {
				$announcement->setDateExpire($this->getData('dateExpire'));
			} else {
				$announcement->setDateExpire(null);
			}
		}

		// Update or insert announcement
		if ($announcement->getId()) {
			$announcementDao->updateObject($announcement);
		} else {
			$announcement->setDatetimePosted(Core::getCurrentDate());
			$announcementDao->insertObject($announcement);
		}

		$contextId = $this->getContextId();

		// Send a notification to associated users
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$notificationUsers = array();
		$allUsers = $userGroupDao->getUsersByContextId($contextId);
		while ($user = $allUsers->next()) {
			$notificationUsers[] = array('id' => $user->getId());
		}
		if (!$this->announcementId) { // Only for new announcements
			foreach ($notificationUsers as $userRole) {
				$notificationManager->createNotification(
					$request, $userRole['id'], NOTIFICATION_TYPE_NEW_ANNOUNCEMENT,
					$contextId, ASSOC_TYPE_ANNOUNCEMENT, $announcement->getId()
				);
			}
			$notificationManager->sendToMailingList($request,
				$notificationManager->createNotification(
					$request, UNSUBSCRIBED_USER_NOTIFICATION, NOTIFICATION_TYPE_NEW_ANNOUNCEMENT,
					$contextId, ASSOC_TYPE_ANNOUNCEMENT, $announcement->getId()
				)
			);
		}
		return $announcement->getId();
	}


	//
	// Protected methods.
	//
	/**
	 * Set the expiry date.
	 * @param $announcement Announcement
	 */
	function setDateExpire($announcement) {
		$dateExpire = $this->getData('dateExpire');
		if ($dateExpire) {
			$announcement->setDateExpire(DAO::formatDateToDB($dateExpire, null, false));
		} else {
			// No date passed but null is acceptable for
			// announcements.
			$announcement->setDateExpire(null);
		}
		return true;
	}
}

?>
