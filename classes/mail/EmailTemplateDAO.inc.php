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
	 * Retrieve an email template by key.
	 * @param $emailKey string
	 * @return EmailTemplate
	 */
	function &getEmailTemplate($emailKey, $journalId) {
		$result = &$this->retrieve(
			'SELECT COALESCE(e.subject, d.subject) AS subject, COALESCE(e.body, d.body) AS body,
		 		d.email_key, d.can_edit, d.can_disable, e.enabled, e.journal_id, e.email_id
		 	FROM email_templates_default AS d LEFT JOIN email_templates AS e ON (d.email_key = e.email_key and e.journal_id = ?)
		 	WHERE d.email_key = ?',
			array($journalId, $emailKey)
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
	 * @return EmailTemplate
	 */
	function &_returnEmailTemplateFromRow(&$row) {
		$emailTemplate = &new EmailTemplate();
		$emailTemplate->setEmailId($row['email_id']);
		$emailTemplate->setJournalId($row['journal_id']);
		$emailTemplate->setEmailKey($row['email_key']);
		$emailTemplate->setSubject($row['subject']);
		$emailTemplate->setBody($row['body']);
		$emailTemplate->setEnabled($row['enabled'] == null ? 1 : $row['enabled']);
	
		return $emailTemplate;
	}

	/**
	 * Insert a new email template.
	 * @param $emailTemplate EmailTemplate
	 */	
	function insertEmailTemplate(&$emailTemplate) {
		return $this->update(
			'INSERT INTO email_templates
				(journal_id, email_key, subject, body, enabled)
				VALUES
				(?, ?, ?, ?, ?)',
			array(
				$emailTemplate->getJournalId(),
				$emailTemplate->getEmailKey(),
				$emailTemplate->getSubject(),
				$emailTemplate->getBody(),
				$emailTemplate->getEnabled()
			)
		);
	}
	
	/**
	 * Update an existing email template.
	 * @param $emailTemplate EmailTemplate
	 */
	function updateEmailTemplate(&$emailTemplate) {
		return $this->update(
			'UPDATE email_templates
				SET	subject = ?,
					body = ?,
					enabled = ?
				WHERE email_id = ?',
			array(
				$emailTemplate->getSubject(),
				$emailTemplate->getBody(),
				$emailTemplate->getEnabled(),
				$emailTemplate->getEmailId()
			)
		);
	}
	
	/**
	 * Delete an email template.
	 * @param $emailTemplate EmailTemplate
	 */
	function deleteEmailTemplate(&$emailTemplate) {
		return $this->deleteEmailTemplateByKey($emailTemplate->getEmailTemplateKey());
	}
	
	/**
	 * Delete an email template by key.
	 * @param $emailKey string
	 * @param $journalId int
	 */
	function deleteEmailTemplateByKey($emailKey, $journalId) {
		return $this->update(
			'DELETE FROM email_templates WHERE email_key = ? AND journal_id = ?', array($emailKey, $journalId)
		);
	}
	
	/**
	 * Retrieve all journals.
	 * @return array Journals ordered by sequence
	 */
	function &getEmailTemplates($journalId) {
		$emailTemplates = array();
		
		$result = &$this->retrieve(
			'SELECT COALESCE(e.subject, d.subject) AS subject, COALESCE(e.body, d.body) AS body,
				d.email_key, d.can_edit, d.can_disable, e.enabled, e.journal_id, e.email_id
			FROM email_templates_default AS d LEFT JOIN email_templates AS e ON (d.email_key = e.email_key AND e.journal_id = ?)
			ORDER BY d.email_key', $journalId
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
	 * @return array
	 */
	function &getEmailTemplateSubjects() {
		$emailTemplates = array();
		
		$result = &$this->retrieve(
			'SELECT email_key, subject FROM email_templates
			 LEFT JOIN email_templates_default ON email_templates.email_key = email_templates_default.email_key'
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
			'SELECT COUNT(*) FROM email_templates WHERE email_key = ?', $emailKey
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
	
}

?>
