<?php

/**
 * @file classes/mail/MassMail.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MassMail
 * @ingroup mail
 *
 * @brief Helper class to send mass emails
 */

// $Id$


import ('mail.MailTemplate');

class MassMail extends MailTemplate {
	var $callback;
	var $frequency;

	/**
	 * Constructor
	 */
	function MassMail($emailKey = null, $locale = null, $enableAttachments = null, $journal = null) {
		parent::MailTemplate($emailKey, $locale, $enableAttachments, $journal);
		$this->callback = null;
		$this->frequency = 10;
	}

	/**
	 * Set the callback function (see PHP's callback pseudotype); this
	 * function will be called for every n emails sent, according to the
	 * frequency.
	 * @param $callback callback
	 */
	function setCallback(&$callback) {
		$this->callback =& $callback;
	}

	/**
	 * Set the frequency at which the callback will be called (i.e. each
	 * n emails).
	 */
	function setFrequency($frequency) {
		$this->frequency = $frequency;
	}

	/**
	 * Send the email.
	 * @return boolean
	 */
	function send() {
		@set_time_limit(0);

		$realRecipients = $this->getRecipients();
		$realSubject = $this->getSubject();
		$realBody = $this->getBody();

		$index = 0;
		$success = true;
		$max = count($realRecipients);
		foreach ($realRecipients as $recipient) {
			$this->clearAllRecipients();

			$this->addRecipient($recipient['email'], $recipient['name']);
			$this->setSubject($realSubject);
			$this->setBody($realBody);

			$success = $success && MailTemplate::send(false);
			$index++;
			if ($this->callback && ($index % $this->frequency) == 0) call_user_func($this->callback, $index, $max);
		}
		$this->setRecipients($realRecipients);
		return $success;
	}
}

?>
