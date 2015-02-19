<?php

/**
 * @file pages/manager/EmailHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for email management functions. 
 */

import('pages.manager.ManagerHandler');

class EmailHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function EmailHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the emails within the current journal.
	 */
	function emails() {
		$this->validate();
		$this->setupTemplate(true);

		$rangeInfo = $this->getRangeInfo('emails');

		$journal =& Request::getJournal();
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates =& $emailTemplateDao->getEmailTemplates(AppLocale::getLocale(), $journal->getId());

		import('lib.pkp.classes.core.ArrayItemIterator');
		$emailTemplates =& ArrayItemIterator::fromRangeInfo($emailTemplates, $rangeInfo);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'manager'), 'manager.journalManagement')));
		$templateMgr->assign_by_ref('emailTemplates', $emailTemplates);
		$templateMgr->assign('helpTopicId','journal.managementPages.emails');
		$templateMgr->display('manager/emails/emails.tpl');
	}

	/**
	 * Create an empty email template.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createEmail($args, &$request) {
		$this->editEmail($args, $request);
	}

	/**
	 * Display form to create/edit an email.
	 * @param $args array if set the first parameter is the key of the email template to edit
	 * @param $request PKPRequest
	 */
	function editEmail($args, &$request) {
		$this->validate();
		$this->setupTemplate(true);

		$journal =& $request->getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array($request->url(null, 'manager', 'emails'), 'manager.emails'));

		$emailKey = !isset($args) || empty($args) ? null : $args[0];

		import('classes.manager.form.EmailTemplateForm');

		$emailTemplateForm = new EmailTemplateForm($emailKey, $journal);
		$emailTemplateForm->initData();
		$emailTemplateForm->display();
	}

	/**
	 * Save changes to an email.
	 */
	function updateEmail() {
		$this->validate();
		$this->setupTemplate(true);
		$journal =& Request::getJournal();

		import('classes.manager.form.EmailTemplateForm');

		$emailKey = Request::getUserVar('emailKey');

		$emailTemplateForm = new EmailTemplateForm($emailKey, $journal);
		$emailTemplateForm->readInputData();

		if ($emailTemplateForm->validate()) {
			$emailTemplateForm->execute();
			Request::redirect(null, null, 'emails');

		} else {
			$emailTemplateForm->display();
		}
	}

	/**
	 * Delete a custom email.
	 * @param $args array first parameter is the key of the email to delete
	 */
	function deleteCustomEmail($args) {
		$this->validate();
		$journal =& Request::getJournal();
		$emailKey = array_shift($args);

		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		if ($emailTemplateDao->customTemplateExistsByKey($emailKey, $journal->getId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $journal->getId());
		}

		Request::redirect(null, null, 'emails');
	}

	/**
	 * Reset an email to default.
	 * @param $args array first parameter is the key of the email to reset
	 */
	function resetEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplateDao->deleteEmailTemplateByKey($args[0], $journal->getId());
		}

		Request::redirect(null, null, 'emails');
	}

	/**
	 * resets all email templates associated with the journal.
	 */
	function resetAllEmails() {
		$this->validate();

		$journal =& Request::getJournal();
		$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByJournal($journal->getId());

		Request::redirect(null, null, 'emails');
	}

	/**
	 * disables an email template.
	 * @param $args array first parameter is the key of the email to disable
	 */
	function disableEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $journal->getId());

			if (isset($emailTemplate)) {
				if ($emailTemplate->getCanDisable()) {
					$emailTemplate->setEnabled(0);

					if ($emailTemplate->getAssocId() == null) {
						$emailTemplate->setAssocId($journal->getId());
						$emailTemplate->setAssocType(ASSOC_TYPE_JOURNAL);
					}

					if ($emailTemplate->getEmailId() != null) {
						$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
					} else {
						$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
					}
				}
			}
		}

		Request::redirect(null, null, 'emails');
	}

	/**
	 * enables an email template.
	 * @param $args array first parameter is the key of the email to enable
	 */
	function enableEmail($args) {
		$this->validate();

		if (isset($args) && !empty($args)) {
			$journal =& Request::getJournal();

			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($args[0], $journal->getId());

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

		Request::redirect(null, null, 'emails');
	}

}

?>
