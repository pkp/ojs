<?php

/**
 * EmailTemplateForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for creating and modifying journal sections.
 *
 * $Id$
 */

class EmailTemplateForm extends Form {

	/** The key of the email template being edited */
	var $emailKey;
	
	/**
	 * Constructor.
	 * @param $emailKey string
	 */
	function EmailTemplateForm($emailKey) {
		parent::Form('manager/emails/emailTemplateForm.tpl');
		
		$this->emailKey = $emailKey;
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'subject', 'required', 'manager.emails.form.subjectRequired'));
		$this->addCheck(new FormValidator(&$this, 'body', 'required', 'manager.emails.form.bodyRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		
		if (isset($this->emailKey)) {
			$journal = &Request::getJournal();
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $journal->getJournalId());
			$templateMgr->assign('canDisable', $emailTemplate->getCanDisable());
		}

		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if (isset($this->emailKey)) {
			$journal = &Request::getJournal();
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $journal->getJournalId());
			
			if ($emailTemplate != null) {
				$this->_data = array(
					'emailId' => $emailTemplate->getEmailId(),
					'emailKey' => $emailTemplate->getEmailKey(),
					'subject' => $emailTemplate->getSubject(),
					'body' => $emailTemplate->getBody(),
					'enabled' => $emailTemplate->getEnabled()
				);
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('emailId', 'subject', 'body', 'enabled', 'journalId', 'emailKey'));
	}
	
	/**
	 * Save email template.
	 */
	function execute() {
		$journal = &Request::getJournal();
			
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		
		if (isset($this->emailKey)) {
			$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $journal->getJournalId());
		
			$emailTemplate->setJournalId($journal->getJournalId());
			$emailTemplate->setEmailId($this->getData('emailId'));
			$emailTemplate->setSubject($this->getData('subject'));
			$emailTemplate->setBody($this->getData('body'));
			
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled($this->getData('enabled'));
			}
			
			if ($emailTemplate->getEmailId() != null) {
				$emailTemplateDao->updateEmailTemplate($emailTemplate);
				$emailId = $emailTemplate->getEmailId();
			} else {
				$emailTemplateDao->insertEmailTemplate($emailTemplate);
				$emailId = $emailTemplateDao->getInsertEmailId();
			}
		}
	}
	
}

?>
