<?php

/**
 * @file controllers/grid/settings/preparedEmails/form/PreparedEmailForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreparedEmailForm
 * @ingroup controllers_modals_preparedEmails_form
 * @see EmailTemplateDAO
 *
 * @brief Form for creating and modifying prepared emails.
 */

import('lib.pkp.classes.form.Form');

class PreparedEmailForm extends Form {

	/** The key of the email template being edited */
	var $_emailKey;

	/** The context of the email template being edited */
	var $_context;

	/**
	 * Constructor.
	 * @param $emailKey string
	 * @param $context Context
	 */
	function __construct($emailKey = null, $context) {
		parent::__construct('controllers/grid/settings/preparedEmails/form/emailTemplateForm.tpl');

		$this->_context = $context;
		$this->setEmailKey($emailKey);

		// Validation checks for this form
		$this->addCheck(new FormValidatorArray($this, 'subject', 'required', 'manager.emails.form.subjectRequired'));
		$this->addCheck(new FormValidatorArray($this, 'body', 'required', 'manager.emails.form.bodyRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'emailKey', 'required', 'manager.emails.form.emailKeyRequired', '/^[a-zA-Z_-]+$/'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Set the email key
	 * @param $emailKey string
	 */
	function setEmailKey($emailKey) {
		$this->_emailKey = $emailKey;
	}

	/**
	 * Get the email key
	 * @return string
	 */
	function getEmailKey() {
		return $this->_emailKey;
	}

	/**
	 * Get the context
	 * @return Context
	 */
	function getContext() {
		return $this->_context;
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData($request) {
		$context = $this->getContext();
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = $emailTemplateDao->getLocaleEmailTemplate($this->getEmailKey(), $context->getId());

		if ($emailTemplate) {
			$subject = array();
			$body = array();
			foreach ($emailTemplate->getLocales() as $locale) {
				$subject[$locale] = $emailTemplate->getSubject($locale);
				$body[$locale] = $emailTemplate->getBody($locale);
			}

			$this->_data = array(
				'emailKey' => $emailTemplate->getEmailKey(),
				'subject' => $subject,
				'body' => $body,
				'description' => $emailTemplate->getDescription(AppLocale::getLocale()),
				'emailKey' => $emailTemplate->getEmailKey(), // Fetched for validation only
			);

		} else {
			$this->setData('isNewTemplate', true);
		}

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_MANAGER);
		$this->setData('supportedLocales', $context->getSupportedLocaleNames());
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		// emailKey is handled outside this form, but need to fetch
		// for validation.
		$this->readUserVars(array('subject', 'body', 'description', 'emailKey'));

		$context = $this->getContext();
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = $emailTemplateDao->getLocaleEmailTemplate($this->getEmailKey(), $context->getId());
		if (!$emailTemplate) $this->setData('isNewTemplate', true);
	}

	/**
	 * Get all locale field names
	 */
	function getLocaleFieldNames() {
		return array('subject', 'body');
	}

	/**
	 * Save email template.
	 */
	function execute() {
		$context = $this->getContext();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplate = $emailTemplateDao->getLocaleEmailTemplate($this->getEmailKey(), $context->getId());

		if (!$emailTemplate) {
			$emailTemplate = new LocaleEmailTemplate();
			$emailTemplate->setCustomTemplate(true);
			$emailTemplate->setCanDisable(false);
			$emailTemplate->setEnabled(true);
			$emailTemplate->setEmailKey($this->getEmailKey());
		}

		$emailTemplate->setAssocType($context->getAssocType());
		$emailTemplate->setAssocId($context->getId());

		$supportedLocales = $context->getSupportedLocaleNames();
		if (!empty($supportedLocales)) {
			foreach ($context->getSupportedLocaleNames() as $localeKey => $localeName) {
				$emailTemplate->setSubject($localeKey, $this->_data['subject'][$localeKey]);
				$emailTemplate->setBody($localeKey, $this->_data['body'][$localeKey]);
			}
		} else {
			$localeKey = AppLocale::getLocale();
			$emailTemplate->setSubject($localeKey, $this->_data['subject'][$localeKey]);
			$emailTemplate->setBody($localeKey, $this->_data['body'][$localeKey]);
		}

		if ($emailTemplate->getEmailId() != null) {
			$emailTemplateDao->updateLocaleEmailTemplate($emailTemplate);
		} else {
			$emailTemplateDao->insertLocaleEmailTemplate($emailTemplate);
		}
	}
}

?>
