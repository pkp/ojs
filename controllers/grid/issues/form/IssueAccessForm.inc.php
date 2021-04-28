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
 *
 * @see Issue
 *
 * @brief Form to edit an issue's access settings
 */

use PKP\form\Form;

use APP\template\TemplateManager;

import('classes.issue.Issue'); // Bring in constants

class IssueAccessForm extends Form
{
    /** @var Issue current issue */
    public $_issue;

    /**
     * Constructor.
     *
     * @param $issue Issue
     */
    public function __construct($issue)
    {
        parent::__construct('controllers/grid/issues/form/issueAccessForm.tpl');
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
        $this->_issue = $issue;
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'accessOptions' => [
                ISSUE_ACCESS_OPEN => 'editor.issues.openAccess',
                ISSUE_ACCESS_SUBSCRIPTION => 'editor.issues.subscription',
            ],
            'issueId' => $this->_issue->getId(),
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * Initialize form data from current issue.
     */
    public function initData()
    {
        $this->_data = [
            'accessStatus' => $this->_issue->getAccessStatus(),
            'openAccessDate' => $this->_issue->getOpenAccessDate(),
        ];
        parent::initData();
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars([
            'accessStatus',
            'openAccessDate',
        ]);
    }

    /**
     * @copydoc Form::execute()
     *
     * @return int Issue ID for created/updated issue
     */
    public function execute(...$functionArgs)
    {
        $journal = Application::get()->getRequest()->getJournal();

        $issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
        $this->_issue->setAccessStatus($this->getData('accessStatus') ? $this->getData('accessStatus') : ISSUE_ACCESS_OPEN);
        if ($openAccessDate = $this->getData('openAccessDate')) {
            $this->_issue->setOpenAccessDate($openAccessDate);
        } else {
            $this->_issue->setOpenAccessDate(null);
        }

        HookRegistry::call('IssueAccessForm::execute', [$this, $this->_issue]);
        $issueDao->updateObject($this->_issue);
        parent::execute(...$functionArgs);
    }
}
