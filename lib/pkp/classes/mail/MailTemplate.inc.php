<?php

/**
 * @file classes/mail/MailTemplate.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MailTemplate
 * @ingroup mail
 *
 * @brief Subclass of Mail for mailing a template email.
 */


import('lib.pkp.classes.mail.Mail');

define('MAIL_ERROR_INVALID_EMAIL', 0x000001);

class MailTemplate extends Mail {
	/** @var object The context this message relates to */
	var $context;

	/** @var boolean whether to include the context's signature */
	var $includeSignature;

	/** @var string Key of the email template we are using */
	var $emailKey;

	/** @var string locale of this template */
	var $locale;

	/** @var boolean email template is enabled */
	var $enabled;

	/** @var array List of errors to display to the user */
	var $errorMessages;

	/** @var boolean whether or not to bcc the sender */
	var $bccSender;

	/** @var boolean Whether or not email fields are disabled */
	var $addressFieldsEnabled;

	/** @var array The list of parameters to be assigned to the template. */
	var $params;

	/** @var string the email header to prepend */
	var $emailHeader;

	/**
	 * Constructor.
	 * @param $emailKey string unique identifier for the template
	 * @param $locale string locale of the template
	 * @param $includeSignature boolean optional
	 */
	function __construct($emailKey = null, $locale = null, $context = null, $includeSignature = true) {
		parent::__construct();
		$this->emailKey = isset($emailKey) ? $emailKey : null;

		// If a context wasn't specified, use the current request.
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		if ($context === null) $context = $request->getContext();

		$this->includeSignature = $includeSignature;
		// Use current user's locale if none specified
		$this->locale = isset($locale) ? $locale : AppLocale::getLocale();

		// Record whether or not to BCC the sender when sending message
		$this->bccSender = $request->getUserVar('bccSender');

		$this->addressFieldsEnabled = true;

		if (isset($this->emailKey)) {
			$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');
			$emailTemplate = $emailTemplateDao->getEmailTemplate($this->emailKey, $this->locale, $context == null ? 0 : $context->getId());
		}

		$userSig = '';
		$user = defined('SESSION_DISABLE_INIT')?null:$request->getUser();
		if ($user && $this->includeSignature) {
			$userSig = $user->getLocalizedSignature();
			if (!empty($userSig)) $userSig = "<br/>" . $userSig;
		}

		if (isset($emailTemplate)) {
			$this->setSubject($emailTemplate->getSubject());
			$this->setBody($emailTemplate->getBody() . $userSig);
			$this->enabled = $emailTemplate->getEnabled();
		} else {
			$this->setBody($userSig);
			$this->enabled = true;
		}

		// Default "From" to user if available, otherwise site/context principal contact
		$this->emailHeader = '';
		if ($user) {
			$this->setFrom($user->getEmail(), $user->getFullName());
		} elseif (is_null($context) || is_null($context->getSetting('contactEmail'))) {
			$site = $request->getSite();
			$this->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		} else {
			$this->setFrom($context->getSetting('contactEmail'), $context->getSetting('contactName'));
			$this->emailHeader = $context->getSetting('emailHeader');
		}

		if ($context) {
			$this->setSubject('[' . $context->getLocalizedAcronym() . '] ' . $this->getSubject());
		}

		$this->context = $context;
		$this->params = array();
	}

	/**
	 * Disable or enable the address fields on the email form.
	 * NOTE: This affects the displayed form ONLY; if disabling the address
	 * fields, callers should manually clearAllRecipients and add/set
	 * recipients just prior to sending.
	 * @param $addressFieldsEnabled boolean
	 */
	function setAddressFieldsEnabled($addressFieldsEnabled) {
		$this->addressFieldsEnabled = $addressFieldsEnabled;
	}

	/**
	 * Get the enabled/disabled state of address fields on the email form.
	 * @return boolean
	 */
	function getAddressFieldsEnabled() {
		return $this->addressFieldsEnabled;
	}

	/**
	 * Check whether or not there were errors in the user input for this form.
	 * @return boolean true iff one or more error messages are stored.
	 */
	function hasErrors() {
		return ($this->errorMessages != null);
	}

	/**
	 * Assigns values to e-mail parameters.
	 * @param $params array Associative array of variables to supply to the email template
	 */
	function assignParams($params = array()) {
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$site = $request->getSite();

		if ($this->context) {
			// Add context-specific variables
			$router = $request->getRouter();
			$dispatcher = $application->getDispatcher();
			$params = array_merge(array(
				'principalContactSignature' => $this->context->getSetting('contactName'),
				'contextName' => $this->context->getLocalizedName(),
				'contextUrl' => $dispatcher->url($request, ROUTE_PAGE, $router->getRequestedContextPath($request)),
			), $params);
		} else {
			// No context available
			$params = array_merge(array(
				'principalContactSignature' => $site->getLocalizedContactName(),
			), $params);
		}

		if (!defined('SESSION_DISABLE_INIT') && ($user = $request->getUser())) {
			// Add user-specific variables
			$params = array_merge(array(
				'senderEmail' => $user->getEmail(),
				'senderName' => $user->getFullName(),
			), $params);
		}

		// Add some general variables
		$params = array_merge(array(
			'siteTitle' => $site->getLocalizedTitle(),
		), $params);

		$this->params = $params;
	}

	/**
	 * Returns true if the email template is enabled; false otherwise.
	 * @return boolean
	 */
	function isEnabled() {
		return $this->enabled;
	}

	/**
	 * Processes form-submitted addresses for inclusion in
	 * the recipient list
	 * @param $currentList array Current recipient/cc/bcc list
	 * @param $newAddresses array "Raw" form parameter for additional addresses
	 */
	function &processAddresses($currentList, &$newAddresses) {
		foreach ($newAddresses as $newAddress) {
			$regs = array();
			// Match the form "My Name <my_email@my.domain.com>"
			if (PKPString::regexp_match_get('/^([^<>' . "\n" . ']*[^<> ' . "\n" . '])[ ]*<(?P<email>' . PCRE_EMAIL_ADDRESS . ')>$/i', $newAddress, $regs)) {
				$currentList[] = array('name' => $regs[1], 'email' => $regs['email']);

			} elseif (PKPString::regexp_match_get('/^<?(?P<email>' . PCRE_EMAIL_ADDRESS . ')>?$/i', $newAddress, $regs)) {
				$currentList[] = array('name' => '', 'email' => $regs['email']);

			} elseif ($newAddress != '') {
				$this->errorMessages[] = array('type' => MAIL_ERROR_INVALID_EMAIL, 'address' => $newAddress);
			}
		}
		return $currentList;
	}

	/**
	 * Send the email.
	 * @return boolean false if there was a problem sending the email
	 */
	function send() {
		if (isset($this->context)) {
			$signature = $this->context->getSetting('emailSignature');
			if (strstr($this->getBody(), '{$templateSignature}') === false) {
				$this->setBody($this->getBody() . "<br/>" . $signature);
			} else {
				$this->setBody(str_replace('{$templateSignature}', $signature, $this->getBody()));
			}

			$envelopeSender = $this->context->getSetting('envelopeSender');
			if (!empty($envelopeSender) && Config::getVar('email', 'allow_envelope_sender')) $this->setEnvelopeSender($envelopeSender);
		}

		$user = defined('SESSION_DISABLE_INIT')?null:Request::getUser();

		if ($user && $this->bccSender) {
			$this->addBcc($user->getEmail(), $user->getFullName());
		}

		// Replace variables in message with values
		$this->replaceParams();

		return parent::send();
	}

	/**
	 * Replace template variables in the message body.
	 * @param $params array Parameters to assign (augments anything provided via setParams)
	 */
	function replaceParams() {
		$subject = $this->getSubject();
		$body = $this->getBody();
		foreach ($this->params as $key => $value) {
			if (!is_object($value)) {
				$subject = str_replace('{$' . $key . '}', $value, $subject);
				$body = str_replace('{$' . $key . '}', $value, $body);
			}
		}
		$this->setSubject($subject);
		$this->setBody($body);
	}

	/**
	 * Assigns user-specific values to email parameters, sends
	 * the email, then clears those values.
	 * @param $params array Associative array of variables to supply to the email template
	 * @return boolean false if there was a problem sending the email
	 */
	function sendWithParams($params) {
		$savedHeaders = $this->getHeaders();
		$savedSubject = $this->getSubject();
		$savedBody = $this->getBody();

		$this->assignParams($params);
		$ret = $this->send();

		$this->setHeaders($savedHeaders);
		$this->setSubject($savedSubject);
		$this->setBody($savedBody);

		return $ret;
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
