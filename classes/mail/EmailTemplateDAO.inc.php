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

import('mail.EmailTemplate');

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
			e.email_id, e.journal_id, d.from_role_id, d.to_role_id
			FROM email_templates_default AS d
			LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			WHERE d.email_key = ?',
			array($journalId, $emailKey)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnBaseEmailTemplateFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
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
			e.email_id, e.journal_id, d.from_role_id, d.to_role_id
			FROM email_templates_default AS d
			LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			WHERE d.email_key = ?',
			array($journalId, $emailKey)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnLocaleEmailTemplateFromRow($result->GetRowAssoc(false));
		} else {
			$result->Close();

			// Check to see if there's a custom email template. This is done in PHP to avoid
			// having to do a full outer join or union in SQL.
			$result = &$this->retrieve(
				'SELECT e.email_key, 1 AS can_edit, 1 AS can_disable, e.enabled, e.email_id, e.journal_id,
				NULL AS from_role_id, NULL AS to_role_id
				FROM email_templates AS e LEFT JOIN email_templates_default AS d ON (e.email_key = d.email_key)
				WHERE d.email_key IS NULL AND e.journal_id = ? AND e.email_key = ?',
				array($journalId, $emailKey)
			);
			if ($result->RecordCount() != 0) {
				$returner = &$this->_returnLocaleEmailTemplateFromRow($result->GetRowAssoc(false));
			}
		}
		$result->Close();
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
		$result = &$this->retrieve(
			'SELECT COALESCE(ed.subject, dd.subject) AS subject, COALESCE(ed.body, dd.body) AS body, COALESCE(e.enabled, 1) AS enabled,
			d.email_key, d.can_edit, d.can_disable, e.journal_id, e.email_id, dd.locale,
			d.from_role_id, d.to_role_id
			FROM email_templates_default AS d NATURAL JOIN email_templates_default_data AS dd
			LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			LEFT JOIN email_templates_data AS ed ON (ed.email_key = e.email_key AND ed.journal_id = e.journal_id AND ed.locale = dd.locale)
			WHERE d.email_key = ? AND dd.locale = ?',
			array($journalId, $emailKey, $locale)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnEmailTemplateFromRow($result->GetRowAssoc(false));
			$returner->setCustomTemplate(false);
		} else {
			$result->Close();

			// Check to see if there's a custom email template. This is done in PHP to avoid
			// having to do a full outer join or union in SQL.
			$result = &$this->retrieve(
				'SELECT ed.subject, ed.body, 1 AS enabled, e.email_key, 1 AS can_edit, 0 AS can_disable,
				e.journal_id, e.email_id, ed.locale, NULL AS from_role_id, NULL AS to_role_id
				FROM email_templates AS e
				LEFT JOIN email_templates_data ed ON (ed.email_key = e.email_key AND ed.journal_id = e.journal_id)
				LEFT JOIN email_templates_default AS d ON (e.email_key = d.email_key)
				WHERE d.email_key IS NULL AND e.journal_id = ? AND e.email_key = ? AND ed.locale = ?',
				array($journalId, $emailKey, $locale)
			);
			if ($result->RecordCount() != 0) {
				$returner = &$this->_returnEmailTemplateFromRow($result->GetRowAssoc(false));
				$returner->setCustomTemplate(true);
			}
		}

		$result->Close();
		return $returner;
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
		$emailTemplate->setFromRoleId($row['from_role_id']);
		$emailTemplate->setToRoleId($row['to_role_id']);
	
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
		$emailTemplate->setFromRoleId($row['from_role_id']);
		$emailTemplate->setToRoleId($row['to_role_id']);

		$emailTemplate->setCustomTemplate(false);
		
		$result = &$this->retrieve(
			'SELECT dd.locale, dd.description, COALESCE(ed.subject, dd.subject) AS subject, COALESCE(ed.body, dd.body) AS body
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
			$emailTemplate->setDescription($dataRow['locale'], $dataRow['description']);
			$result->MoveNext();
		}
		$result->Close();

		// Retrieve custom email contents as well; this is done in PHP to avoid
		// using a SQL outer join or union.
		$result = &$this->retrieve(
			'SELECT ed.locale, ed.subject, ed.body
			FROM email_templates_data AS ed
			LEFT JOIN email_templates_default_data AS dd ON (ed.email_key = dd.email_key AND dd.locale = ed.locale)
			WHERE ed.journal_id = ? AND ed.email_key = ? AND dd.email_key IS NULL',
			array($row['journal_id'], $row['email_key'])
		);

		while (!$result->EOF) {
			$dataRow = &$result->GetRowAssoc(false);
			$emailTemplate->addLocale($dataRow['locale']);
			$emailTemplate->setSubject($dataRow['locale'], $dataRow['subject']);
			$emailTemplate->setBody($dataRow['locale'], $dataRow['body']);
			$result->MoveNext();

			$emailTemplate->setCustomTemplate(true);
		}
		$result->Close();

		return $emailTemplate;
	}

	/**
	 * Internal function to return an email template object from a row.
	 * @param $row array
	 * @return EmailTemplate
	 */
	function &_returnEmailTemplateFromRow(&$row, $isCustomTemplate=null) {
		$emailTemplate = &new EmailTemplate();
		$emailTemplate->setEmailId($row['email_id']);
		$emailTemplate->setJournalId($row['journal_id']);
		$emailTemplate->setEmailKey($row['email_key']);
		$emailTemplate->setLocale($row['locale']);
		$emailTemplate->setSubject($row['subject']);
		$emailTemplate->setBody($row['body']);
		$emailTemplate->setEnabled($row['enabled'] == null ? 1 : $row['enabled']);
		$emailTemplate->setCanDisable($row['can_disable']);
		$emailTemplate->setFromRoleId($row['from_role_id']);
		$emailTemplate->setToRoleId($row['to_role_id']);
	
		if ($isCustomTemplate !== null) {
			$emailTemplate->setCustomTemplate($isCustomTemplate);
		}

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
		return $emailTemplate->getEmailId();
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
		 	d.email_key, d.can_edit, d.can_disable, e.journal_id, e.email_id, dd.locale,
			d.from_role_id, d.to_role_id
		 	FROM email_templates_default AS d NATURAL JOIN email_templates_default_data AS dd
		 	LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			LEFT JOIN email_templates_data AS ed ON (ed.email_key = e.email_key AND ed.journal_id = e.journal_id AND ed.locale = dd.locale)
		 	WHERE dd.locale = ?',
			array($journalId, $locale)
		);
		
		while (!$result->EOF) {
			$emailTemplates[] = &$this->_returnEmailTemplateFromRow($result->GetRowAssoc(false), false);
			$result->moveNext();
		}
		$result->Close();

		// Fetch custom email templates as well; this is done in PHP
		// to avoid a union or full outer join call in SQL.
		$result = &$this->retrieve(
			'SELECT ed.subject AS subject, ed.body AS body, e.enabled AS enabled, e.email_key,
			1 AS can_edit, 1 AS can_disable, e.journal_id, e.email_id, ed.locale,
			NULL AS from_role_id, NULL AS to_role_id
			FROM email_templates AS e LEFT JOIN email_templates_data AS ed ON (e.email_key = ed.email_key AND ed.journal_id = e.journal_id)
			LEFT JOIN email_templates_default AS d ON (ed.email_key = d.email_key)
			WHERE e.journal_id = ? AND ed.locale = ? AND d.email_key IS NULL',
			array($journalId, $locale)
		);

		while (!$result->EOF) {
			$emailTemplates[] = &$this->_returnEmailTemplateFromRow($result->GetRowAssoc(false), true);
			$result->moveNext();
		}

		// Sort all templates by email key.
		$compare = create_function('$t1, $t2', 'return strcmp($t1->getEmailKey(), $t2->getEmailKey());');
		usort ($emailTemplates, $compare);

		return $emailTemplates;
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
	
	/**
	 * Check if a template exists with the given email key for a journal.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return boolean
	 */
	function templateExistsByKey($emailKey, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM email_templates
				WHERE email_key = ?
				AND   journal_id = ?',
			array(
				$emailKey,
				$journalId
			)
		);
		if (isset($result->fields[0]) && $result->fields[0] != 0) return true;
		$result->Close();

		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM email_templates_default
				WHERE email_key = ?',
			$emailKey
		);
		if (isset($result->fields[0]) && $result->fields[0] != 0) return true;
		return false;
	}

	/**
	 * Check if a custom template exists with the given email key for a journal.
	 * @param $emailKey string
	 * @param $journalId int
	 * @return boolean
	 */
	function customTemplateExistsByKey($emailKey, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
				FROM email_templates e LEFT JOIN email_templates_default d ON (e.email_key = d.email_key)
				WHERE e.email_key = ? AND d.email_key IS NULL AND e.journal_id = ?',
			array(
				$emailKey,
				$journalId
			)
		);
		if (isset($result->fields[0]) && $result->fields[0] != 0) return true;
		return false;
	}
}

?>
