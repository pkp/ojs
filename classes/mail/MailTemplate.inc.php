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
	
	/** @var $emailKey string Key of the email template we are using */
	var $emailKey;
	
	/**
	 * Constructor.
	 */
	function MailTemplate($emailKey = null) {
		$this->emailKey = isset($emailKey) ? $emailKey : null;
		
		$journal = &Request::getJournal();
					
		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = &$emailTemplateDao->getEmailTemplate($this->emailKey, $journal == null ? 0 : $journal->getJournalId());
		
		$this->setSubject($emailTemplate->getSubject());
		$this->setBody($emailTemplate->getBody());
		
		// Default "From" to site/journal principal contact
		if ($journal == null) {
			$site = &Request::getSite();
			$this->setFrom($site->getContactEmail(), $site->getContactName());
			
		} else {
			$this->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		}
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
			$subject = str_replace('{$' . $key . '}', $value, $subject);
			$body = str_replace('{$' . $key . '}', $value, $body);
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
		$form->setData('subject', $this->getSubject());
		$form->setData('body', $this->getBody());
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
}

?>
