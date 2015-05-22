<?php

/**
 * @file classes/mail/EmailTemplateDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplateDAO
 * @ingroup mail
 * @see EmailTemplate
 *
 * @brief Operations for retrieving and modifying Email Template objects.
 */

import('lib.pkp.classes.mail.PKPEmailTemplateDAO');
import('lib.pkp.classes.mail.EmailTemplate');

class EmailTemplateDAO extends PKPEmailTemplateDAO {
	/**
	 * Retrieve a base email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return BaseEmailTemplate
	 */
	function &getBaseEmailTemplate($emailKey, $journalId) {
		$returner =& parent::getBaseEmailTemplate($emailKey, ASSOC_TYPE_JOURNAL, $journalId);
		return $returner;
	}

	/**
	 * Retrieve localized email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return LocaleEmailTemplate
	 */
	function &getLocaleEmailTemplate($emailKey, $journalId) {
		$returner =& parent::getLocaleEmailTemplate($emailKey, ASSOC_TYPE_JOURNAL, $journalId);
		return $returner;
	}

	/**
	 * Retrieve an email template by key.
	 * @param $emailKey string
	 * @param $locale string
	 * @param $journalId int
	 * @return EmailTemplate
	 */
	function &getEmailTemplate($emailKey, $locale, $journalId) {
		$returner =& parent::getEmailTemplate($emailKey, $locale, ASSOC_TYPE_JOURNAL, $journalId);
		return $returner;
	}

	/**
	 * Delete an email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 */
	function deleteEmailTemplateByKey($emailKey, $journalId) {
		return parent::deleteEmailTemplateByKey($emailKey, ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * Retrieve all email templates.
	 * @param $locale string
	 * @param $journalId int
	 * @param $rangeInfo object optional
	 * @return array Email templates
	 */
	function &getEmailTemplates($locale, $journalId, $rangeInfo = null) {
		$returner =& parent::getEmailTemplates($locale, ASSOC_TYPE_JOURNAL, $journalId, $rangeInfo);
		return $returner;
	}

	/**
	 * Delete all email templates for a specific journal.
	 * @param $journalId int
	 */
	function deleteEmailTemplatesByJournal($journalId) {
		return parent::deleteEmailTemplatesByAssoc(ASSOC_TYPE_JOURNAL, $journalId);
	}

	/**
	 * Check if a template exists with the given email key for a journal.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return boolean
	 */
	function templateExistsByKey($emailKey, $journalId = null) {
		return parent::templateExistsByKey($emailKey, $journalId!==null?ASSOC_TYPE_JOURNAL:null, $journalId);
	}

	/**
	 * Check if a custom template exists with the given email key for a journal.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return boolean
	 */
	function customTemplateExistsByKey($emailKey, $journalId) {
		return parent::customTemplateExistsByKey($emailKey, ASSOC_TYPE_JOURNAL, $journalId);
	}
}

?>
