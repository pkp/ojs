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

class MailTemplate extends Mail {
	
	// Key of the email template we are using.
	var $emailKey;
	
	/**
	 * Constructor.
	 */
	function MailTemplate($emailKey = null) {
		$this->emailKey = isset($emailKey) ? $emailKey : null;
		
		$journal = &Request::getJournal();
					
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $journal->getJournalId());
		
		$this->setSubject($emailTemplate->getSubject());
		$this->setBody($emailTemplate->getBody());
	}
	
	function assignParams($paramArray) {
		$this->setSubject(@preg_replace("/\{(\w+)\}/e", "\$paramArray['\\1']", $this->getSubject()));
		$this->setBody(@preg_replace("/\{(\w+)\}/e", "\$paramArray['\\1']", $this->getBody()));
		
		return;
	}
	
	function isEnabled() {
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey);
		
		return $emailTemplate->getEnabled();	
	}
	
	function displayEditForm($formActionUrl, $hiddenFormParams = null) {
		$journal = &Request::getJournal();
		$form = new Form('manager/emails/customEmailTemplateForm.tpl');
					
		$form->setData('formActionUrl', $formActionUrl);
		$form->setData('subject', $this->subject);
		$form->setData('body', $this->body);
		if ($hiddenFormParams != null) {
			$form->setData('hiddenFormParams', $hiddenFormParams);
		}
			
		$form->display();
	}
}

?>
