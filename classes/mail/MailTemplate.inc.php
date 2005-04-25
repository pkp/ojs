<?php

/**
 * MailTemplate.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * Subclass of Mail for mailing a template email.
 *
 * $Id$
 */

import('mail.Mail');

define('MAIL_ERROR_INVALID_EMAIL', 0x000001);
class MailTemplate extends Mail {
	
	/** @var $emailKey string Key of the email template we are using */
	var $emailKey;
	
	/** @var $locale string locale of this template */
	var $locale;
	
	/** @var $enabled boolean email template is enabled */
	var $enabled;

	/** @var $errorMessages array List of errors to display to the user */
	var $errorMessages;

	/** @var $persistAttachments array List of temporary files belonging to
	    email; these are maintained between requests and only sent to the
	    attachment handling functions in Mail.inc.php at time of send. */
	var $persistAttachments;
	var $attachmentsEnabled;

	/**
	 * Constructor.
	 * @param $emailKey string unique identifier for the template
	 * @param $locale string locale of the template
	 */
	function MailTemplate($emailKey = null, $locale = null, $enableAttachments = false) {
		$this->emailKey = isset($emailKey) ? $emailKey : null;

		
		// Use current user's locale if none specified
		$this->locale = isset($locale) ? $locale : Locale::getLocale();
		
		$journal = &Request::getJournal();
		
		if (isset($this->emailKey)) {
			$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $this->locale, $journal == null ? 0 : $journal->getJournalId());
		}

		if (isset($emailTemplate) && Request::getUserVar('subject')==null && Request::getUserVar('body')==null) {
			$this->setSubject($emailTemplate->getSubject());
			$this->setBody($emailTemplate->getBody());
			$this->enabled = $emailTemplate->getEnabled();
			
		} else {
			$this->setSubject(Request::getUserVar('subject'));
			$this->setBody(Request::getUserVar('body'));
			$this->enabled = true;

			if (is_array(Request::getUserVar('to'))) {
				$this->setRecipients($this->processAddresses ($this->getRecipients(), Request::getUserVar('to')));
			}
			if (is_array(Request::getUserVar('cc'))) {
				$this->setCcs($this->processAddresses ($this->getCcs(), Request::getUserVar('cc')));
			}
			if (is_array(Request::getUserVar('bcc'))) {
				$this->setBccs($this->processAddresses ($this->getBccs(), Request::getUserVar('bcc')));
			}
		}
		
		// Default "From" to site/journal principal contact
		if ($journal == null) {
			$site = &Request::getSite();
			$this->setFrom($site->getContactEmail(), $site->getContactName());
			
		} else {
			if (!Request::getUserVar('continued')) $this->setSubject('[' . $journal->getSetting('journalInitials') . '] ' . $this->getSubject());
			$this->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		}

		if ($enableAttachments) {
			$user = Request::getUser();
			$this->_handleAttachments($user->getUserId());
		} else {
			$this->attachmentsEnabled = false;
		}
	}

	/**
	 * Check whether or not there were errors in the user input for this form.
	 * @return boolean true iff one or more error messages are stored.
	 */
	function hasErrors() {
		return ($this->errorMessages != null);
	}

	/**
	 * Assigns values to e-mail parameters.
	 * @param $paramArray array
	 * @return void
	 */
	function assignParams($paramArray = array()) {
		$subject = $this->getSubject();
		$body = $this->getBody();

		// Add commonly-used variables to the list
		$journal = &Request::getJournal();
		if (isset($journal)) {
			// FIXME Include affiliation, title, etc. in signature?
			$paramArray['journalName'] = $journal->getTitle();
			$paramArray['principalContactSignature'] = $journal->getSetting('contactName');
		} else {
			$site = &Request::getSite();
			$paramArray['principalContactSignature'] = $site->getContactName();
		}
		if (!isset($paramArray['journalUrl'])) $paramArray['journalUrl'] = Request::getIndexUrl() . '/' . Request::getRequestedJournalPath();

		// Replace variables in message with values
		foreach ($paramArray as $key => $value) {
			if (!is_object($value)) {
				$subject = str_replace('{$' . $key . '}', $value, $subject);
				$body = str_replace('{$' . $key . '}', $value, $body);
			}
		}
		
		$this->setSubject($subject);
		$this->setBody($body);
	}
	
	/**
	 * Returns true if the email template is enabled; false otherwise.
	 * @return boolean
	 */
	function isEnabled() {
		return $this->enabled;
	}

	/**
	 * Processes form-submitted addresses for inclusion in
	 * the recipient list
	 * @param $currentList array Current recipient/cc/bcc list
	 * @param $newAddresses array "Raw" form parameter for additional addresses
	 */
	function &processAddresses($currentList, &$newAddresses) {
		foreach ($newAddresses as $newAddress) {
			$regs = array();
			// Match the form "My Name <my_email@my.domain.com>"
			if (ereg('^([-A-Za-z0-9_ ]+)[ ]+<([-A-Za-z0-9]+([\-_\+\.][A-Za-z0-9]+)*@[A-Za-z0-9]+([-_\.][A-Za-z0-9]+)*\.[A-Za-z]{2,})>$', $newAddress, &$regs)) {
				$currentList[] = array('name' => $regs[1], 'email' => $regs[2]);
			} elseif (ereg('^[A-Za-z0-9]+([\-_\+\.][A-Za-z0-9]+)*@[A-Za-z0-9]+([\-_\.][A-Za-z0-9]+)*\.[A-Za-z]{2,}$', $newAddress)) {
				$currentList[] = array('name' => '', 'email' => $newAddress);
			} else if ($newAddress != '') {
				$this->errorMessages[] = array('type' => MAIL_ERROR_INVALID_EMAIL, 'address' => $newAddress);
			}
		}
		return $currentList;
	}

	/**
	 * Displays an edit form to customize the email.
	 * @param $formActionUrl string
	 * @param $hiddenFormParams array
	 * @return void
	 */
	function displayEditForm($formActionUrl, $hiddenFormParams = null, $alternateTemplate = null, $additionalParameters = array()) {
		$journal = &Request::getJournal();
		import('form.Form');
		$form = new Form($alternateTemplate!=null?$alternateTemplate:'email/email.tpl');

		$form->setData('formActionUrl', $formActionUrl);
		$form->setData('subject', $this->getSubject());
		$form->setData('body', $this->getBody());

		$form->setData('to', $this->getRecipients());
		$form->setData('cc', $this->getCcs());
		$form->setData('bcc', $this->getBccs());
		$form->setData('blankTo', Request::getUserVar('blankTo'));
		$form->setData('blankCc', Request::getUserVar('blankCc'));
		$form->setData('blankBcc', Request::getUserVar('blankBcc'));
		$form->setData('from', $this->getFromString());

		if ($this->attachmentsEnabled) {
			$form->setData('attachmentsEnabled', true);
			$form->setData('persistAttachments', $this->persistAttachments);
		}

		$form->setData('errorMessages', $this->errorMessages);

		if ($hiddenFormParams != null) {
			$form->setData('hiddenFormParams', $hiddenFormParams);
		}

		foreach ($additionalParameters as $key => $value) {
			$form->setData($key, $value);
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.emails.sendEmail');
			
		$form->display();
	}

	/**
	 * Send the email.
	 * Aside from calling the parent method, this actually attaches
	 * the persistent attachments if they are used.
	 */
	function send() {
		if ($this->attachmentsEnabled) {
			foreach ($this->persistAttachments as $persistentAttachment) {
				$this->addAttachment(
					$persistentAttachment->getFilePath(),
					$persistentAttachment->getOriginalFileName(),
					$persistentAttachment->getFileType()
				);
			}
		}
		$result = parent::send();

		if ($this->attachmentsEnabled) {
			$user = Request::getUser();
			$this->_clearAttachments($user->getUserId());
		}

		return $result;
	}

	/**
	 * Assigns user-specific values to email parameters, sends
	 * the email, then clears those values.
	 * @param $paramArray array
	 * @return void
	 */
	function sendWithParams($paramArray) {
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();
		
		$this->assignParams($paramArray);
		
		$ret = $this->send();
		
		$this->setSubject($savedSubject);
		$this->setBody($savedBody);
		
		return $ret;
	}
	
	/**
	 * Clears the recipient, cc, and bcc lists.
	 * @param $clearHeaders boolean if true, also clear headers
	 * @return void
	 */
	function clearRecipients($clearHeaders = true) {
		$this->setData('recipients', null);
		$this->setData('ccs', null);
		$this->setData('bccs', null);
		if ($clearHeaders) {
			$this->setData('headers', null);
		}
	}

	/**
	 * Adds a persistent attachment to the current list.
	 * Persistent attachments MUST be previously initialized
	 * with handleAttachments.
	 */
	function addPersistAttachment($temporaryFile) {
		$this->persistAttachments[] = $temporaryFile;
	}

	/**
	 * Handles attachments in a generalized manner in situations where
	 * an email message must span several requests. Called from the
	 * constructor when attachments are enabled.
	 */
	function _handleAttachments($userId) {
		import('file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();

		$this->attachmentsEnabled = true;
		$this->persistAttachments = array();

		$deleteAttachment = Request::getUserVar('deleteAttachment');

		if (Request::getUserVar('persistAttachments') != null) foreach (Request::getUserVar('persistAttachments') as $fileId) {
			$temporaryFile = $temporaryFileManager->getFile($fileId, $userId);
			if (!empty($temporaryFile)) {
				if ($deleteAttachment != $temporaryFile->getFileId()) {
					$this->persistAttachments[] = $temporaryFile;
				} else {
					// This file is being deleted.
					$temporaryFileManager->deleteFile($temporaryFile->getFileId(), $userId);
				}
			}
		}

		if (Request::getUserVar('addAttachment')) {
			$user = &Request::getUser();

			$this->persistAttachments[] = $temporaryFileManager->handleUpload('newAttachment', $user->getUserId());
		}
	}

	/**
	 * Delete all attachments associated with this message.
	 * Called from send().
	 * @param $userId int
	 */
	function _clearAttachments($userId) {
		import('file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();

		foreach (Request::getUserVar('persistAttachments') as $fileId) {
			$temporaryFile = $temporaryFileManager->getFile($fileId, $userId);
			if (!empty($temporaryFile)) {
				$temporaryFileManager->deleteFile($temporaryFile->getFileId(), $userId);
			}
		}
	}
}

?>
