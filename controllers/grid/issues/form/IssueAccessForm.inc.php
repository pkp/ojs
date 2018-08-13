<?php

/**
 * @file controllers/grid/issues/form/IssueAccessForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueAccessForm
 * @ingroup controllers_grid_issues_form
 * @see Issue
 *
 * @brief Form to edit an issue's access settings
 */

import('lib.pkp.classes.form.Form');
import('classes.issue.Issue'); // Bring in constants

class IssueAccessForm extends Form {
	/** @var Issue current issue */
	var $_issue;

	/**
	 * Constructor.
	 * @param $issue Issue
	 */
	function __construct($issue) {
		parent::__construct('controllers/grid/issues/form/issueAccessForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->_issue = $issue;
	}

	/**
	 * Fetch the form.
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'accessOptions' => array(
				ISSUE_ACCESS_OPEN => 'editor.issues.openAccess',
				ISSUE_ACCESS_SUBSCRIPTION => 'editor.issues.subscription',
			),
			'issueId' => $this->_issue->getId(),
		));
		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current issue.
	 * @param $request PKPRequest
	 */
	function initData() {
		$this->_data = array(
			'accessStatus' => $this->_issue->getAccessStatus(),
			'openAccessDate' => $this->_issue->getOpenAccessDate(),
		);
		parent::initData();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'accessStatus',
			'openAccessDate',
		));
	}

	/**
	 * Save issue settings.
	 * @param $request PKPRequest
	 * @return int Issue ID for created/updated issue
	 */
	function execute($request) {
		$journal = $request->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$this->_issue->setAccessStatus($this->getData('accessStatus') ? $this->getData('accessStatus') : ISSUE_ACCESS_OPEN);
		if ($openAccessDate = $this->getData('openAccessDate')) $this->_issue->setOpenAccessDate($openAccessDate);
		else $this->_issue->setOpenAccessDate(null);

		HookRegistry::call('IssueAccessForm::execute', array($this, $this->_issue));
		$issueDao->updateObject($this->_issue);
	}
}

?>
