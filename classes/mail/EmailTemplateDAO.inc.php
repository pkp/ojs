<?php

/**
 * EmailTemplateDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * Class for Email Template DAO.
 * Operations for retrieving and modifying Email Template objects.
 *
 * $Id$
 */

class EmailTemplateDAO extends DAO {

	/**
	 * Constructor.
	 */
	function EmailTemplateDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a base email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return BaseEmailTemplate
	 */
	function &getBaseEmailTemplate($emailKey, $journalId) {
		$result = &$this->retrieve(
			'SELECT d.email_key, d.can_edit, d.can_disable, COALESCE(e.enabled, 1) AS enabled,
			e.email_id, e.journal_id
			FROM email_templates_default AS d
			LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			WHERE d.email_key = ?',
			array($journalId, $emailKey)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnBaseEmailTemplateFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve localized email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return LocaleEmailTemplate
	 */
	function &getLocaleEmailTemplate($emailKey, $journalId) {
		$result = &$this->retrieve(
			'SELECT d.email_key, d.can_edit, d.can_disable, COALESCE(e.enabled, 1) AS enabled,
			e.email_id, e.journal_id
			FROM email_templates_default AS d
			LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			WHERE d.email_key = ?',
			array($journalId, $emailKey)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnLocaleEmailTemplateFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve an email template by key.
	 * @param $emailKey string
	 * @param $locale string
	 * @param $journalId int
	 * @return EmailTemplate
	 */
	function &getEmailTemplate($emailKey, $locale, $journalId) {
		$result = &$this->retrieve(
			'SELECT COALESCE(ed.subject, dd.subject) AS subject, COALESCE(ed.body, dd.body) AS body, COALESCE(e.enabled, 1) AS enabled,
			d.email_key, d.can_edit, d.can_disable, e.journal_id, e.email_id, dd.locale
			FROM email_templates_default AS d NATURAL JOIN email_templates_default_data AS dd
			LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			LEFT JOIN email_templates_data AS ed ON (ed.email_key = e.email_key AND ed.journal_id = e.journal_id AND ed.locale = dd.locale)
			WHERE d.email_key = ? AND dd.locale = ?',
			array($journalId, $emailKey, $locale)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnEmailTemplateFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Internal function to return an email template object from a row.
	 * @param $row array
	 * @return BaseEmailTemplate
	 */
	function &_returnBaseEmailTemplateFromRow(&$row) {
		$emailTemplate = &new BaseEmailTemplate();
		$emailTemplate->setEmailId($row['email_id']);
		$emailTemplate->setJournalId($row['journal_id']);
		$emailTemplate->setEmailKey($row['email_key']);
		$emailTemplate->setEnabled($row['enabled'] == null ? 1 : $row['enabled']);
		$emailTemplate->setCanDisable($row['can_disable']);
	
		return $emailTemplate;
	}

	/**
	 * Internal function to return an email template object from a row.
	 * @param $row array
	 * @return LocaleEmailTemplate
	 */
	function &_returnLocaleEmailTemplateFromRow(&$row) {
		$emailTemplate = &new LocaleEmailTemplate();
		$emailTemplate->setEmailId($row['email_id']);
		$emailTemplate->setJournalId($row['journal_id']);
		$emailTemplate->setEmailKey($row['email_key']);
		$emailTemplate->setEnabled($row['enabled'] == null ? 1 : $row['enabled']);
		$emailTemplate->setCanDisable($row['can_disable']);
		
		$result = &$this->retrieve(
			'SELECT dd.locale, COALESCE(ed.subject, dd.subject) AS subject, COALESCE(ed.body, dd.body) AS body
			FROM email_templates_default_data AS dd
			LEFT JOIN email_templates_data AS ed ON (dd.email_key = ed.email_key AND dd.locale = ed.locale AND ed.journal_id = ?)
			WHERE dd.email_key = ?',
			array($row['journal_id'], $row['email_key'])
		);
		
		while (!$result->EOF) {
			$dataRow = &$result->GetRowAssoc(false);
			$emailTemplate->addLocale($dataRow['locale']);
			$emailTemplate->setSubject($dataRow['locale'], $dataRow['subject']);
			$emailTemplate->setBody($dataRow['locale'], $dataRow['body']);
			$result->MoveNext();
		}
		$result->Close();
	
		return $emailTemplate;
	}

	/**
	 * Internal function to return an email template object from a row.
	 * @param $row array
	 * @return EmailTemplate
	 */
	function &_returnEmailTemplateFromRow(&$row) {
		$emailTemplate = &new EmailTemplate();
		$emailTemplate->setEmailId($row['email_id']);
		$emailTemplate->setJournalId($row['journal_id']);
		$emailTemplate->setEmailKey($row['email_key']);
		$emailTemplate->setLocale($row['locale']);
		$emailTemplate->setSubject($row['subject']);
		$emailTemplate->setBody($row['body']);
		$emailTemplate->setEnabled($row['enabled'] == null ? 1 : $row['enabled']);
		$emailTemplate->setCanDisable($row['can_disable']);
	
		return $emailTemplate;
	}

	/**
	 * Insert a new base email template.
	 * @param $emailTemplate BaseEmailTemplate
	 */	
	function insertBaseEmailTemplate(&$emailTemplate) {
		return $this->update(
			'INSERT INTO email_templates
				(journal_id, email_key, enabled)
				VALUES
				(?, ?, ?)',
			array(
				$emailTemplate->getJournalId(),
				$emailTemplate->getEmailKey(),
				$emailTemplate->getEnabled() == null ? 0 : 1
			)
		);
		$emailTemplate->setEmailId($this->getInsertEmailId());
	}
	
	/**
	 * Update an existing base email template.
	 * @param $emailTemplate BaseEmailTemplate
	 */
	function updateBaseEmailTemplate(&$emailTemplate) {
		return $this->update(
			'UPDATE email_templates
				SET	enabled = ?
				WHERE email_id = ?',
			array(
				$emailTemplate->getEnabled() == null ? 0 : 1,
				$emailTemplate->getEmailId()
			)
		);
	}

	/**
	 * Insert a new localized email template.
	 * @param $emailTemplate LocaleEmailTemplate
	 */	
	function insertLocaleEmailTemplate(&$emailTemplate) {
		$this->insertBaseEmailTemplate($emailTemplate);
		return $this->updateLocaleEmailTemplateData($emailTemplate);
	}
	
	/**
	 * Update an existing localized email template.
	 * @param $emailTemplate LocaleEmailTemplate
	 */
	function updateLocaleEmailTemplate(&$emailTemplate) {
		$this->updateBaseEmailTemplate($emailTemplate);
		return $this->updateLocaleEmailTemplateData($emailTemplate);
	}
	
	/**
	 * Insert/update locale-specific email template data.
	 * @param $emailTemplate LocaleEmailTemplate
	 */
	function updateLocaleEmailTemplateData(&$emailTemplate) {
		foreach ($emailTemplate->getLocales() as $locale) {
			$result = &$this->retrieve(
				'SELECT COUNT(*) FROM email_templates_data
				WHERE email_key = ? AND locale = ? AND journal_id = ?',
				array($emailTemplate->getEmailKey(), $locale, $emailTemplate->getJournalId())
			);
			
			if ($result->fields[0] == 0) {
				$this->update(
					'INSERT INTO email_templates_data
					(email_key, locale, journal_id, subject, body)
					VALUES
					(?, ?, ?, ?, ?)',
					array($emailTemplate->getEmailKey(), $locale, $emailTemplate->getJournalId(), $emailTemplate->getSubject($locale), $emailTemplate->getBody($locale))
				);
				
			} else {
				$this->update(
					'UPDATE email_templates_data
					SET subject = ?,
						body = ?
					WHERE email_key = ? AND locale = ? AND journal_id = ?',
					array($emailTemplate->getSubject($locale), $emailTemplate->getBody($locale), $emailTemplate->getEmailKey(), $locale, $emailTemplate->getJournalId())
				);
			}
		}
	}
	
	/**
	 * Delete an email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 */
	function deleteEmailTemplateByKey($emailKey, $journalId) {
		$this->update(
			'DELETE FROM email_templates_data WHERE email_key = ? AND journal_id = ?',
			array($emailKey, $journalId)
		);
		return $this->update(
			'DELETE FROM email_templates WHERE email_key = ? AND journal_id = ?',
			array($emailKey, $journalId)
		);
	}
	
	/**
	 * Retrieve all journals.
	 * @param $locale string
	 * @param $journalId int
	 * @return array Journals ordered by sequence
	 */
	function &getEmailTemplates($locale, $journalId) {
		$emailTemplates = array();
		
		$result = &$this->retrieve(
			'SELECT COALESCE(ed.subject, dd.subject) AS subject, COALESCE(ed.body, dd.body) AS body, COALESCE(e.enabled, 1) AS enabled,
		 	d.email_key, d.can_edit, d.can_disable, e.journal_id, e.email_id, dd.locale
		 	FROM email_templates_default AS d NATURAL JOIN email_templates_default_data AS dd
		 	LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			LEFT JOIN email_templates_data AS ed ON (ed.email_key = e.email_key AND ed.journal_id = e.journal_id AND ed.locale = dd.locale)
		 	WHERE dd.locale = ?
		 	ORDER BY d.email_key',
			array($journalId, $locale)
		);
		
		while (!$result->EOF) {
			$emailTemplates[] = &$this->_returnEmailTemplateFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $emailTemplates;
	}
	
	/**
	 * Retrieve the keys and subjects of all email templates in an associative array.
	 * @param $locale string
	 * @param $journalId int
	 * @return array
	 */
	function &getEmailTemplateSubjects($locale, $journalId) {
		$emailTemplates = array();
		
		$result = &$this->retrieve(
			'SELECT dd.email_key, COALESCE(ed.subject, dd.subject) AS subject
			FROM email_templates_default_data AS dd
			LEFT JOIN email_templates_data AS ed ON (ed.email_key = dd.email_key AND ed.journal_id = ? AND ed.locale = dd.locale)
		 	WHERE dd.locale = ?
		 	ORDER BY subject',
			array($journalId, $locale)
		);
		
		while (!$result->EOF) {
			$emailTemplates[$result->fields[0]] = $result->fields[1];
			$result->moveNext();
		}
		$result->Close();
	
		return $emailTemplates;
	}
	
	/**
	 * Check if an email exists with a specified key.
	 * @param $key the key of the email template
	 * @return boolean
	 */
	function emailTemplateExistsByKey($emailKey) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM email_templates_default WHERE email_key = ?', $emailKey
		);
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Get the ID of the last inserted email template.
	 * @return int
	 */
	function getInsertEmailId() {
		return $this->getInsertId('email_templates', 'emailId');
	}
	
	/**
	 * Delete all email templates for a specific journal.
	 * @param $journalId int
	 */
	function deleteEmailTemplatesByJournal($journalId) {
		$this->update(
			'DELETE FROM email_templates_data WHERE journal_id = ?', $journalId
		);
		return $this->update(
			'DELETE FROM email_templates WHERE journal_id = ?', $journalId
		);
	}
	
	/**
	 * Delete all email templates for a specific locale.
	 * @param $locale string
	 */
	function deleteEmailTemplatesByLocale($locale) {
		$this->update(
			'DELETE FROM email_templates_data WHERE locale = ?', $locale
		);
	}
	
	/**
	 * Delete all default email templates for a specific locale.
	 * @param $locale string
	 */
	function deleteDefaultEmailTemplatesByLocale($locale) {
		$this->update(
			'DELETE FROM email_templates_default_data WHERE locale = ?', $locale
		);
	}
	
}

?>
