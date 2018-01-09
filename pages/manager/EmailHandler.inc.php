<?php

/**
 * @file pages/manager/EmailHandler.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	
	/**
	 * Export the selected email templates as XML
	 * @param $args array
	 * @@param $request PKPRequest
	 */
	function exportEmails($args, $request) {
		$this->validate();
		import('lib.pkp.classes.xml.XMLCustomWriter');
		
		$selectedEmailKeys = (array) $request->getUserVar('tplId');
		if (empty($selectedEmailKeys)) {
			$request->redirect(null, null, 'emails');
		}
		
		$journal = Request::getJournal();
		$doc = XMLCustomWriter::createDocument();
		$emailTexts = XMLCustomWriter::createElement($doc, 'email_texts');
		$emailTexts->setAttribute('locale', AppLocale::getLocale());
		$emailTexts->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplates = $emailTemplateDao->getEmailTemplates(AppLocale::getLocale(), $journal->getId());
		
		foreach($emailTemplates as $emailTemplate) {
			$emailKey = $emailTemplate->getData('emailKey');
			if (!in_array($emailKey, $selectedEmailKeys)) continue;
			
			$subject = $emailTemplate->getData('subject');
			$body = $emailTemplate->getData('body');
			
			$emailTextNode = XMLCustomWriter::createElement($doc, 'email_text');
			XMLCustomWriter::setAttribute($emailTextNode, 'key', $emailKey);
			
			//append subject node
			$subjectNode = XMLCustomWriter::createChildWithText($doc, $emailTextNode, 'subject', $subject, false);
			XMLCustomWriter::appendChild($emailTextNode, $subjectNode);
			
			//append body node
			$bodyNode = XMLCustomWriter::createChildWithText($doc, $emailTextNode, 'body', $body, false);
			XMLCustomWriter::appendChild($emailTextNode, $bodyNode);
			
			//append email_text node
			XMLCustomWriter::appendChild($emailTexts, $emailTextNode);
		}
		
		XMLCustomWriter::appendChild($doc, $emailTexts);
		
		header("Content-Type: application/xml");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; filename=\"email-templates-" . date('Y-m-d-H-i-s') . ".xml\"");
		
		XMLCustomWriter::printXML($doc);
	}
	
	/**
	 * Upload a custom email template file
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadEmails($args, $request) {
		$this->validate();
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		$journal = $request->getJournal();
		$journalId = $journal->getId();
		
		$uploadName = 'email_file';
		$fileName = $fileManager->getUploadedFileName($uploadName);
		if (!$fileName) {
			$request->redirect(null, null, 'emails');
		}
		
		$filesDir = Config::getVar('files', 'files_dir');
		$filePath = $filesDir . '/journals/' . $journalId . '/' . $fileName;
		
		if (!$fileManager->uploadError($uploadName)) {
			if ($fileManager->uploadedFileExists($uploadName)) {
				$uploadedFilePath = $fileManager->getUploadedFilePath($uploadName);
				if ($this->_saveEmailTemplates($uploadedFilePath, $journal)) {
					if ($fileManager->deleteFile($uploadedFilePath)) {
						$this->_showMessage($request);
						$request->redirect(null, null, 'emails');
					}
				}
			}
		}
		
		$this->_showMessage($request, false);
		$request->redirect(null, null, 'emails');
	}
	
	/**
	 * Save a custom email template file
	 * @param $filePath string
	 * @param $journalId int
	 * @return boolean
	 */
	function _saveEmailTemplates($filePath, $journal) {
		$this->validate();
		import('lib.pkp.classes.xml.XMLParser');
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		
		$xmlParser = new XMLParser();
		
		$struct = $xmlParser->parseStruct($filePath);
		$locale = $struct['email_texts'][0]['attributes']['locale'];

		$emailTexts = $struct['email_text'];
		$subjects = $struct['subject'];
		$bodies = $struct['body'];
		
		// check if the parsed xml has the correct structure
		if (!$emailTexts || !$subjects || !$bodies) return false;

		$nodeSizes = array(count($emailTexts), count($subjects), count($bodies));
		if (count(array_unique($nodeSizes)) > 1) return false;

		$journalId = $journal->getId();
		$supportedLocales = $journal->getSupportedLocaleNames();

		foreach($emailTexts as $index => $emailText) {
			$emailKey = $emailText['attributes']['key'];
			$subject = $subjects[$index]['value'];
			$body = $bodies[$index]['value'];

			$emailTemplate = $emailTemplateDao->getLocaleEmailTemplate($emailKey, $journalId);
			$emailTemplateLocaleData = $emailTemplate->localeData;
			
			// just update supported locales
			foreach($emailTemplateLocaleData as $emailTemplateLocale => $data) {
				if (!isset($supportedLocales[$emailTemplateLocale])) {
					unset($emailTemplateLocaleData[$emailTemplateLocale]);
				}
			}
			$emailTemplate->localeData = $emailTemplateLocaleData; 
			
			$emailTemplate->setAssocType(ASSOC_TYPE_JOURNAL);
			$emailTemplate->setAssocId($journalId);
			
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled($emailTemplate->getData('enabled'));
			}
			
			$emailTemplate->setSubject($locale, $subject);
			$emailTemplate->setBody($locale, $body);

			if ($emailTemplate->getEmailId() != null) {
				$emailTemplateDao->updateLocaleEmailTemplate($emailTemplate);
			} else {
				$emailTemplateDao->insertLocaleEmailTemplate($emailTemplate);
			}
		}
		return true;
	}
	
	/**
	 * Show success or error message
	 * @param $request PKPRequest
	 * @param $success boolean
	 */
	function _showMessage($request, $success = true) {
		$this->validate();
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		
		if ($success == true) {
			$notificationType = NOTIFICATION_TYPE_SUCCESS;
			$message = 'manager.emails.uploadSuccess';
		} else {
			$notificationType = NOTIFICATION_TYPE_ERROR;
			$message = 'manager.emails.uploadError';
		}
		
		$user = $request->getUser();
		$notificationManager->createTrivialNotification(
			$user->getId(),
			$notificationType,
			array('contents' => __($message))
		);
	}
}
?>