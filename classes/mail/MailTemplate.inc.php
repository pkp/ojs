<?php

/**
 * @file classes/mail/MailTemplate.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MailTemplate
 * @ingroup mail
 *
 * @brief Subclass of PKPMailTemplate for mailing a template email.
 */

import('lib.pkp.classes.mail.PKPMailTemplate');

class MailTemplate extends PKPMailTemplate {
	/** @var $journal object The journal this message relates to */
	var $journal;

	/**
	 * Constructor.
	 * @param $emailKey string unique identifier for the template
	 * @param $locale string locale of the template
	 * @param $enableAttachments boolean optional Whether or not to enable article attachments in the template
	 * @param $journal object optional The journal this message relates to
	 * @param $includeSignature boolean optional
	 * @param $ignorePostedData boolean optional
	 */
	function MailTemplate($emailKey = null, $locale = null, $enableAttachments = null, $journal = null, $includeSignature = true, $ignorePostedData = false) {
		parent::PKPMailTemplate($emailKey, $locale, $enableAttachments, $includeSignature);

		// If a journal wasn't specified, use the current request.
		if ($journal === null) $journal =& Request::getJournal();

		if (isset($this->emailKey)) {
			$emailTemplateDao =& DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate =& $emailTemplateDao->getEmailTemplate($this->emailKey, $this->locale, $journal == null ? 0 : $journal->getId());
		}

		$userSig = '';
		$user =& Request::getUser();
		if ($user && $includeSignature) {
			$userSig = $user->getLocalizedSignature();
			if (!empty($userSig)) $userSig = "\n" . $userSig;
		}

		if (isset($emailTemplate) && ($ignorePostedData || (Request::getUserVar('subject')==null && Request::getUserVar('body')==null))) {
			$this->setSubject($emailTemplate->getSubject());
			$this->setBody($emailTemplate->getBody() . $userSig);
			$this->enabled = $emailTemplate->getEnabled();

			if (Request::getUserVar('usePostedAddresses')) {
				$to = Request::getUserVar('to');
				if (is_array($to)) {
					$this->setRecipients($this->processAddresses ($this->getRecipients(), $to));
				}
				$cc = Request::getUserVar('cc');
				if (is_array($cc)) {
					$this->setCcs($this->processAddresses ($this->getCcs(), $cc));
				}
				$bcc = Request::getUserVar('bcc');
				if (is_array($bcc)) {
					$this->setBccs($this->processAddresses ($this->getBccs(), $bcc));
				}
			}
		} else {
			$this->setSubject(Request::getUserVar('subject'));
			$body = Request::getUserVar('body');
			if (empty($body)) $this->setBody($userSig);
			else $this->setBody($body);
			$this->skip = (($tmp = Request::getUserVar('send')) && is_array($tmp) && isset($tmp['skip']));
			$this->enabled = true;

			if (is_array($toEmails = Request::getUserVar('to'))) {
				$this->setRecipients($this->processAddresses ($this->getRecipients(), $toEmails));
			}
			if (is_array($ccEmails = Request::getUserVar('cc'))) {
				$this->setCcs($this->processAddresses ($this->getCcs(), $ccEmails));
			}
			if (is_array($bccEmails = Request::getUserVar('bcc'))) {
				$this->setBccs($this->processAddresses ($this->getBccs(), $bccEmails));
			}
		}

		// Default "From" to user if available, otherwise site/journal principal contact
		$user =& Request::getUser();
		if ($user) {
			$this->setFrom($user->getEmail(), $user->getFullName());
		} elseif (is_null($journal) || is_null($journal->getSetting('contactEmail'))) {
			$site =& Request::getSite();
			$this->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());

		} else {
			$this->setFrom($journal->getSetting('contactEmail'), $journal->getSetting('contactName'));
		}

		if ($journal && !Request::getUserVar('continued')) {
			$this->setSubject('[' . $journal->getLocalizedSetting('initials') . '] ' . $this->getSubject());
		}

		$this->journal =& $journal;
	}

	/**
	 * Assigns values to e-mail parameters.
	 * @param $paramArray array
	 * @return void
	 */
	function assignParams($paramArray = array()) {
		// Add commonly-used variables to the list
		if (isset($this->journal)) {
			// FIXME Include affiliation, title, etc. in signature?
			$paramArray['journalName'] = $this->journal->getLocalizedTitle();
			$paramArray['principalContactSignature'] = $this->journal->getSetting('contactName');
		} else {
			$site =& Request::getSite();
			$paramArray['principalContactSignature'] = $site->getLocalizedContactName();
		}
		if (!isset($paramArray['journalUrl'])) $paramArray['journalUrl'] = Request::url(Request::getRequestedJournalPath());

		return parent::assignParams($paramArray);
	}

	/**
	 * Displays an edit form to customize the email.
	 * @param $formActionUrl string
	 * @param $hiddenFormParams array
	 * @return void
	 */
	function displayEditForm($formActionUrl, $hiddenFormParams = null, $alternateTemplate = null, $additionalParameters = array()) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'journal.managementPages.emails');

		parent::displayEditForm($formActionUrl, $hiddenFormParams, $alternateTemplate, $additionalParameters);
	}

	/**
	 * Send the email.
	 * Aside from calling the parent method, this actually attaches
	 * the persistent attachments if they are used.
	 * @param $clearAttachments boolean Whether to delete attachments after
	 */
	function send($clearAttachments = true) {
		if (isset($this->journal)) {
			//If {$templateSignature} exists in the body of the
			// message, replace it with the journal signature;
			// otherwise just append it. This is here to
			// accomodate MIME-encoded messages or other cases
			// where the signature cannot just be appended.
			$searchString = '{$templateSignature}';
			if (strstr($this->getBody(), $searchString) === false) {
				$this->setBody($this->getBody() . "\n" . $this->journal->getSetting('emailSignature'));
			} else {
				$this->setBody(str_replace($searchString, $this->journal->getSetting('emailSignature'), $this->getBody()));
			}

			$envelopeSender = $this->journal->getSetting('envelopeSender');
			if (!empty($envelopeSender) && Config::getVar('email', 'allow_envelope_sender')) $this->setEnvelopeSender($envelopeSender);
		}

		return parent::send($clearAttachments);
	}
}

?>
