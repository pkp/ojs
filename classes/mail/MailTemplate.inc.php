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
	
	/**
	 * Assigns values to e-mail parameters.
	 * @param $paramArray array
	 * @return void
	 */
	function assignParams($paramArray) {
		$subject = $this->getSubject();
		$body = $this->getBody();
		
		foreach ($paramArray as $key => $value) {
			$subject = preg_replace("/{".$key."}/", $value, $subject);
			$body = preg_replace("/{".$key."}/", $value, $body);
		}
		
		$this->setSubject($subject);
		$this->setBody($body);
	}
	
	/**
	 * Returns true if the email template is enabled; false otherwise.
	 * @return boolean
	 */
	function isEnabled() {
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey);
		
		return $emailTemplate->getEnabled();	
	}
	
	/**
	 * Displays an edit form to customize the email.
	 * @param $formActionUrl string
	 * @param $hiddenFormParams array
	 * @return void
	 */
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
		
		$this->send();
		
		$this->setSubject($savedSubject);
		$this->setBody($savedBody);
	}
	
	/**
	 * Clears the recipient, cc, and bcc lists.
	 * @return void
	 */
	function clearRecipients() {
		$this->setData('recipients', null);
		$this->setData('ccs', null);
		$this->setData('bccs', null);
		$this->setData('headers', null);
	}
}

?>
