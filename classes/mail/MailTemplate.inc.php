<?php

/**
 * @file classes/mail/MailTemplate.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MailTemplate
 * @ingroup mail
 *
 * @brief Subclass of PKPMailTemplate for mailing a template email.
 */

import('lib.pkp.classes.mail.PKPMailTemplate');

class MailTemplate extends PKPMailTemplate {
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
		parent::PKPMailTemplate($emailKey, $locale, $enableAttachments, $journal, $includeSignature);
	}

	/**
	 * Assigns values to e-mail parameters.
	 * @param $paramArray array
	 * @return void
	 */
	function assignParams($paramArray = array()) {
		// Add commonly-used variables to the list
		if (isset($this->context)) {
			// FIXME Include affiliation, title, etc. in signature?
			$paramArray['journalName'] = $this->context->getLocalizedName();
		}
		if (!isset($paramArray['journalUrl'])) $paramArray['journalUrl'] = Request::url(Request::getRequestedJournalPath());

		return parent::assignParams($paramArray);
	}
}

?>
