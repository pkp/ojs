<?php

/**
 * EmailHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for email management functions. 
 *
 * $Id$
 */

class EmailHandler extends ManagerHandler {

	/**
	 * Display a list of the sections within the current journal.
	 */
	function emails() {
		parent::validate();
		parent::setupTemplate(true);

		$journal = &Request::getJournal();
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates = &$emailTemplateDao->getEmailTemplates(Locale::getLocale(), $journal->getJournalId());
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array('manager', 'manager.journalManagement')));
		$templateMgr->assign('emailTemplates', $emailTemplates);
		$templateMgr->display('manager/emails/emails.tpl');
	}
	
	/**
	 * Display form to create/edit a section.
	 * @param $args array optional, if set the first parameter is the key of the email template to edit
	 */
	function editEmail($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		$emailKey = !isset($args) || empty($args) ? null : $args[0];
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->emailTemplateExistsByKey($emailKey) == true) {
			import('manager.form.EmailTemplateForm');
		
			$emailTemplateForm = &new EmailTemplateForm($emailKey);
			$emailTemplateForm->initData();
			$emailTemplateForm->display();
		
		} else {
				Request::redirect('manager/emails');
		}
	}
	
	/**
	 * Save changes to a section.
	 */
	function updateEmail() {
		parent::validate();
		
		import('manager.form.EmailTemplateForm');
		
		$emailKey = Request::getUserVar('emailKey');
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->emailTemplateExistsByKey($emailKey) == true) {
			$emailTemplateForm = &new EmailTemplateForm($emailKey);
			$emailTemplateForm->readInputData();
			
			if ($emailTemplateForm->validate()) {
				$emailTemplateForm->execute();
				Request::redirect('manager/emails');
				
			} else {
				parent::setupTemplate(true);
				$emailTemplateForm->display();
			}
			
		} else {
				Request::redirect('manager/emails');
		}
	}
	
	/**
	 * Reset an email to default.
	 * @param $args array first parameter is the key of the email to reset
	 */
	function resetEmail($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
		
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteEmailTemplateByKey($args[0], $journal->getJournalId());
		}
		
		Request::redirect('manager/emails');
	}
	
	/**
	 * resets all email templates associated with the journal.
	 */
	function resetAllEmails() {
		parent::validate();
		
		$journal = &Request::getJournal();
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByJournal($journal->getJournalId());
		
		Request::redirect('manager/emails');
	}
	
	/**
	 * disables an email template.
	 * @param $args array first parameter is the key of the email to disable
	 */
	function disableEmail($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
		
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $journal->getJournalId());
			
			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(0);
					
					if ($emailTemplate->getJournalId() == null) {
						$emailTemplate->setJournalId($journal->getJournalId());
					}
			
					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}
		
		Request::redirect('manager/emails');
	}
	
	/**
	 * enables an email template.
	 * @param $args array first parameter is the key of the email to enable
	 */
	function enableEmail($args) {
		parent::validate();
		
		if (isset($args) && !empty($args)) {
			$journal = &Request::getJournal();
		
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $journal->getJournalId());
			
			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(1);
					
					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}
		
		Request::redirect('manager/emails');
	}
	
}

?>
