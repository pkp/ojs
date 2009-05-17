<?php

/**
 * @file LoginHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LoginHandler
 * @ingroup pages_login
 *
 * @brief Handle login/logout requests. 
 */


import('pages.login.PKPLoginHandler');

class LoginHandler extends PKPLoginHandler {
	/**
	 * Constructor
	 **/
	function LoginHandler() {
		parent::PKPLoginHandler();
	}

	/**
	 * Helper Function - set mail from address
	 * @param MailTemplate $mail 
	 */
	function _setMailFrom(&$mail) {
		$site =& Request::getSite();
		$journal =& Request::getJournal();
		
		// Set the sender based on the current context
		if ($journal && $journal->getSetting('supportEmail')) {
			$mail->setFrom($journal->getSetting('supportEmail'), $journal->getSetting('supportName'));
		} else { 
			$mail->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		}
	}
}

?>