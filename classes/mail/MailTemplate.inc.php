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
	
	// Subject and body of the email template we are using.
	var $subject;
	var $body;

	/**
	 * Constructor.
	 */
	function MailTemplate($emailKey = null) {
		$this->emailKey = isset($emailKey) ? $emailKey : null;
		
		$journal = &Request::getJournal();
					
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $journal->getJournalId());
		
		$this->subject = $emailTemplate->getSubject();
		$this->body = $emailTemplate->getBody();
	}
	
	function assignParams($paramArray) {
		$this->subject = @preg_replace("/\{(\w+)\}/e", "\$paramArray['\\1']", $this->subject);
		$this->body = @preg_replace("/\{(\w+)\}/e", "\$paramArray['\\1']", $this->body);
		
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
