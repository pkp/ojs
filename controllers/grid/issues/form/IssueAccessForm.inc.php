<?php

/**
 * @file controllers/grid/issues/form/IssueAccessForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'accessOptions' => array(
				ISSUE_ACCESS_OPEN => 'editor.issues.openAccess',
				ISSUE_ACCESS_SUBSCRIPTION => 'editor.issues.subscription',
			),
			'issueId' => $this->_issue->getId(),
		));
		return parent::fetch($request, $template, $display);
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
	 * @copydoc Form::execute()
	 * @return int Issue ID for created/updated issue
	 */
	function execute(...$functionArgs) {
		$journal = Application::get()->getRequest()->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$this->_issue->setAccessStatus($this->getData('accessStatus') ? $this->getData('accessStatus') : ISSUE_ACCESS_OPEN);
		if ($openAccessDate = $this->getData('openAccessDate')) $this->_issue->setOpenAccessDate($openAccessDate);
		else $this->_issue->setOpenAccessDate(null);

		HookRegistry::call('IssueAccessForm::execute', array($this, $this->_issue));
		$issueDao->updateObject($this->_issue);
		parent::execute(...$functionArgs);
	}
}


