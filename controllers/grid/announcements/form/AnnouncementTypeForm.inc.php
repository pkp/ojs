<?php
/**
 * @file controllers/grid/announcements/form/AnnouncementTypeForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeForm
 * @ingroup controllers_grid_announcements_form
 *
 * @brief Form for to read/create/edit announcement types.
 */


import('lib.pkp.classes.manager.form.PKPAnnouncementTypeForm');

class AnnouncementTypeForm extends PKPAnnouncementTypeForm {
	/** @var $journalId int */
	var $journalId;

	/**
	 * Constructor
	 * @param $journalId int
	 * @param $announcementTypeId int leave as default for new announcement
	 */
	function AnnouncementTypeForm($journalId, $announcementTypeId = null) {
		parent::PKPAnnouncementTypeForm($announcementTypeId);
		$this->journalId = $journalId;
	}


	//
	// Extended methods from Form
	//
	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('typeId', $this->typeId);
		return parent::fetch($request, 'controllers/grid/announcements/form/announcementTypeForm.tpl');
	}

	//
	// Private helper methdos.
	//
	/**
	 * Helper function to assign the AssocType and the AssocId
	 * @param AnnouncementType the announcement type to be modified
	 */
	function _setAnnouncementTypeAssocId(&$announcementType) {
		$journalId = $this->journalId;
		$announcementType->setAssocType(ASSOC_TYPE_JOURNAL);
		$announcementType->setAssocId($journalId);
	}
}

?>
