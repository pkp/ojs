<?php

/**
 * GroupForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for journal managers to create/edit groups.
 *
 * $Id$
 */

import('form.Form');

class GroupForm extends Form {

	/** @var groupId int the ID of the group being edited */
	var $group;

	/**
	 * Constructor
	 * @param group Group object; null to create new
	 */
	function GroupForm($group = null) {
		$journal = &Request::getJournal();

		parent::Form('manager/groups/groupForm.tpl');
	
		// Group title is provided
		$this->addCheck(new FormValidator($this, 'title', 'required', 'manager.groups.form.groupTitleRequired'));

		$this->group =& $group;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('group', $this->group);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.groups');
		parent::display();
	}
	
	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		if ($this->group != null) {
			$this->_data = array(
				'title' => $this->group->getTitle(),
				'titleAlt1' => $this->group->getTitleAlt1(),
				'titleAlt2' => $this->group->getTitleAlt2()
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'titleAlt1', 'titleAlt2'));
	}
	
	/**
	 * Save group group. 
	 */
	function execute() {
		$groupDao = &DAORegistry::getDAO('GroupDAO');
		$journal = &Request::getJournal();
	
		if (!isset($this->group)) {
			$this->group = &new Group();
		}
		
		$this->group->setJournalId($journal->getJournalId());
		$this->group->setTitle($this->getData('title'));
		$this->group->setTitleAlt1($this->getData('titleAlt1'));
		$this->group->setTitleAlt2($this->getData('titleAlt2'));

		// Eventually this will be a general Groups feature; for now,
		// we're just using it to display journal team entries in About.
		$this->group->setAboutDisplayed(true);

		// Update or insert group group
		if ($this->group->getGroupId() != null) {
			$groupDao->updateGroup($this->group);
		} else {
			// Kludge: Assume we'll have less than 10,000 group groups.
			$this->group->setSequence(10000);

			$groupDao->insertGroup($this->group);

			// Re-order the groups so the new one is at the end of the list.
			$groupDao->resequenceGroups($this->group->getJournalId());
		}
	}
	
}

?>
