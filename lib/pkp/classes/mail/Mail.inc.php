<?php

/**
 * @defgroup mail Mail
 * Mail delivery code.
 */

/**
 * @file classes/mail/Mail.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mail
 * @ingroup mail
 *
 * @brief Class defining basic operations for handling and sending emails.
 */

define('MAIL_WRAP', 76);

class Mail extends DataObject {
	/** @var array List of key => value private parameters for this message */
	var $privateParams;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->privateParams = array();
		if (Config::getVar('email', 'allow_envelope_sender')) {
			$defaultEnvelopeSender = Config::getVar('email', 'default_envelope_sender');
			if (!empty($defaultEnvelopeSender)) $this->setEnvelopeSender($defaultEnvelopeSender);
		}
	}

	/**
	 * Add a private parameter to this email. Private parameters are
	 * replaced just before sending and are never available via getBody etc.
	 */
	function addPrivateParam($name, $value) {
		$this->privateParams[$name] = $value;
	}

	/**
	 * Set the entire list of private parameters.
	 * @see addPrivateParam
	 */
	function setPrivateParams($privateParams) {
		$this->privateParams = $privateParams;
	}

	/**
	 * Add a recipient.
	 * @param $email string
	 * @param $name string optional
	 */
	function addRecipient($email, $name = '') {
		if (($recipients = $this->getData('recipients')) == null) {
			$recipients = array();
		}
		array_push($recipients, array('name' => $name, 'email' => $email));

		$this->setData('recipients', $recipients);
	}

	/**
	 * Set the envelope sender (bounce address) for the message,
	 * if supported.
	 * @param $envelopeSender string Email address
	 */
	function setEnvelopeSender($envelopeSender) {
		$this->setData('envelopeSender', $envelopeSender);
	}

	/**
	 * Get the envelope sender (bounce address) for the message, if set.
	 * Override any set envelope sender if force_default_envelope_sender config option is in effect.
	 * @return string
	 */
	function getEnvelopeSender() {
		if (Config::getVar('email', 'force_default_envelope_sender') && Config::getVar('email', 'default_envelope_sender')) {
			return Config::getVar('email', 'default_envelope_sender');
		} else {
			return $this->getData('envelopeSender');
		}
	}


	/**
	 * Get the message content type (MIME)
	 * @return string
	 */
	function getContentType() {
		return $this->getData('content_type');
	}

	/**
	 * Set the message content type (MIME)
	 * @param $contentType string
	 */
	function setContentType($contentType) {
		$this->setData('content_type', $contentType);
	}

	/**
	 * Get the recipients for the message.
	 * @return array
	 */
	function getRecipients() {
		return $this->getData('recipients');
	}

	/**
	 * Set the recipients for the message.
	 * @param $recipients array
	 */
	function setRecipients($recipients) {
		$this->setData('recipients', $recipients);
	}

	/**
	 * Add a carbon-copy (CC) recipient to the message.
	 * @param $email string
	 * @param $name string optional
	 */
	function addCc($email, $name = '') {
		if (($ccs = $this->getData('ccs')) == null) {
			$ccs = array();
		}
		array_push($ccs, array('name' => $name, 'email' => $email));

		$this->setData('ccs', $ccs);
	}

	/**
	 * Get the carbon-copy (CC) recipients for the message.
	 * @return array
	 */
	function getCcs() {
		return $this->getData('ccs');
	}

	/**
	 * Set the carbon-copy (CC) recipients for the message.
	 * @param $ccs array
	 */
	function setCcs($ccs) {
		$this->setData('ccs', $ccs);
	}

	/**
	 * Add a blind carbon copy (BCC) recipient to the message.
	 * @param $email string
	 * @param $name optional
	 */
	function addBcc($email, $name = '') {
		if (($bccs = $this->getData('bccs')) == null) {
			$bccs = array();
		}
		array_push($bccs, array('name' => $name, 'email' => $email));

		$this->setData('bccs', $bccs);
	}

	/**
	 * Get the blind carbon copy (BCC) recipients for the message
	 * @return array
	 */
	function getBccs() {
		return $this->getData('bccs');
	}

	/**
	 * Set the blind carbon copy (BCC) recipients for the message.
	 * @param $bccs array
	 */
	function setBccs($bccs) {
		$this->setData('bccs', $bccs);
	}

	/**
	 * If no recipients for this message, promote CC'd accounts to
	 * recipients. If recipients exist, no effect.
	 * @return boolean true iff CCs were promoted
	 */
	function promoteCcsIfNoRecipients() {
		$ccs = $this->getCcs();
		$recipients = $this->getRecipients();
		if (empty($recipients)) {
			$this->setRecipients($ccs);
			$this->setCcs(array());
			return true;
		}
		return false;
	}

	/**
	 * Clear all recipients for this message (To, CC, and BCC).
	 */
	function clearAllRecipients() {
		$this->setRecipients(array());
		$this->setCcs(array());
		$this->setBccs(array());
	}

	/**
	 * Add an SMTP header to the message.
	 * @param $name string
	 * @param $content string
	 */
	function addHeader($name, $content) {
		$updated = false;

		if (($headers = $this->getData('headers')) == null) {
			$headers = array();
		}

		foreach ($headers as $key => $value) {
			if ($value['name'] == $name) {
				$headers[$key]['content'] = $content;
				$updated = true;
			}
		}

		if (!$updated) {
			array_push($headers, array('name' => $name,'content' => $content));
		}

		$this->setData('headers', $headers);
	}

	/**
	 * Get the SMTP headers for the message.
	 * @return array
	 */
	function getHeaders() {
		return $this->getData('headers');
	}

	/**
	 * Set the SMTP headers for the message.
	 * @param $headers array
	 */
	function setHeaders(&$headers) {
		$this->setData('headers', $headers);
	}

	/**
	 * Adds a file attachment to the email.
	 * @param $filePath string complete path to the file to attach
	 * @param $fileName string attachment file name (optional)
	 * @param $contentType string attachment content type (optional)
	 * @param $contentDisposition string attachment content disposition, inline or attachment (optional, default attachment)
	 */
	function addAttachment($filePath, $fileName = '', $contentType = '', $contentDisposition = 'attachment') {
		if ($attachments =& $this->getData('attachments') == null) {
			$attachments = array();
		}

		/* If the arguments $fileName and $contentType are not specified,
			then try and determine them automatically. */
		if (empty($fileName)) {
			$fileName = basename($filePath);
		}

		if (empty($contentType)) {
			$contentType = PKPString::mime_content_type($filePath);
			if (empty($contentType)) $contentType = 'application/x-unknown-content-type';
		}

		array_push($attachments, array(
			'path' => $filePath,
			'filename' => $fileName,
			'content-type' => $contentType
		));

		$this->setData('attachments', $attachments);
	}

	/**
	 * Get the attachments currently on the message.
	 * @return array
	 */
	function &getAttachments() {
		$attachments =& $this->getData('attachments');
		return $attachments;
	}

	/**
	 * Return true iff attachments are included in this message.
	 * @return boolean
	 */
	function hasAttachments() {
		$attachments =& $this->getAttachments();
		return ($attachments != null && count($attachments) != 0);
	}

	/**
	 * Set the sender of the message.
	 * @param $email string
	 * @param $name string optional
	 */
	function setFrom($email, $name = '') {
		$this->setData('from', array('name' => $name, 'email' => $email));
	}

	/**
	 * Get the sender of the message.
	 * @return array
	 */
	function getFrom() {
		return $this->getData('from');
	}

	/**
	 * Set the reply-to of the message.
	 * @param $email string or null to clear
	 * @param $name string optional
	 */
	function setReplyTo($email, $name = '') {
		if ($email === null) $this->setData('replyTo', null);
		$this->setData('replyTo', array('name' => $name, 'email' => $email));
	}

	/**
	 * Get the reply-to of the message.
	 * @return array
	 */
	function getReplyTo() {
		return $this->getData('replyTo');
	}

	/**
	 * Return a string containing the reply-to address.
	 * @return string
	 */
	function getReplyToString($send = false) {
		$replyTo = $this->getReplyTo();
		if (!array_key_exists('email', $replyTo) || $replyTo['email'] == null) {
			return null;
		} else {
			return (Mail::encodeDisplayName($replyTo['name'], $send) . ' <'.$replyTo['email'].'>');
		}
	}

	/**
	 * Set the subject of the message.
	 * @param $subject string
	 */
	function setSubject($subject) {
		$this->setData('subject', $subject);
	}

	/**
	 * Get the subject of the message.
	 * @return string
	 */
	function getSubject() {
		return $this->getData('subject');
	}

	/**
	 * Set the body of the message.
	 * @param $body string
	 */
	function setBody($body) {
		$this->setData('body', $body);
	}

	/**
	 * Get the body of the message.
	 * @return string
	 */
	function getBody() {
		return $this->getData('body');
	}

	/**
	 * Return a string containing the from address.
	 * Override any from address if force_default_envelope_sender config option is in effect.
	 * @return string
	 */
	function getFromString($send = false) {
		$from = $this->getFrom();
		if ($from == null) {
			return null;
		} 
		return (Mail::encodeDisplayName($from['name'], $send) . ' <'.$from['email'].'>');
	}

	/**
	 * Return a string from an array of (name, email) pairs.
	 * @param $includeNames boolean
	 * @return string;
	 */
	function getAddressArrayString($addresses, $includeNames = true, $send = false) {
		if ($addresses == null) {
			return null;

		} else {
			$addressString = '';

			foreach ($addresses as $address) {
				if (!empty($addressString)) {
					$addressString .= ', ';
				}

				if (Core::isWindows() || empty($address['name']) || !$includeNames) {
					$addressString .= $address['email'];

				} else {
					$addressString .= Mail::encodeDisplayName($address['name'], $send) . ' <'.$address['email'].'>';
				}
			}

			return $addressString;
		}
	}

	/**
	 * Return a string containing the recipients.
	 * @return string
	 */
	function getRecipientString() {
		return $this->getAddressArrayString($this->getRecipients());
	}

	/**
	 * Return a string containing the Cc recipients.
	 * @return string
	 */
	function getCcString() {
		return $this->getAddressArrayString($this->getCcs());
	}

	/**
	 * Return a string containing the Bcc recipients.
	 * @return string
	 */
	function getBccString() {
		return $this->getAddressArrayString($this->getBccs(), false);
	}


	/**
	 * Send the email.
	 * @return boolean
	 */
	function send() {
		if (HookRegistry::call('Mail::send', array($this))) return;

		// Replace all the private parameters for this message.
		$mailBody = $this->getBody();
		if (is_array($this->privateParams)) {
			foreach ($this->privateParams as $name => $value) {
				$mailBody = str_replace($name, $value, $mailBody);
			}
		}

		require_once('lib/pkp/lib/vendor/phpmailer/phpmailer/class.phpmailer.php');
		$mailer = new PHPMailer();
		$mailer->IsHTML(true);
		if (Config::getVar('email', 'smtp')) {
			$mailer->IsSMTP();
			$mailer->Port = Config::getVar('email', 'smtp_port');
			if (($s = Config::getVar('email', 'smtp_auth')) != '') {
				$mailer->SMTPSecure = $s;
				$mailer->SMTPAuth = true;
			}
			$mailer->Host = Config::getVar('email', 'smtp_server');
			$mailer->Username = Config::getVar('email', 'smtp_username');
			$mailer->Password = Config::getVar('email', 'smtp_password');
		}
		$mailer->CharSet = Config::getVar('i18n', 'client_charset');
		if (($t = $this->getContentType()) != null) $mailer->ContentType = $t;
		$mailer->XMailer = 'Public Knowledge Project Suite v2';
		$mailer->WordWrap = MAIL_WRAP;
		foreach ((array) $this->getHeaders() as $header) {
			$mailer->AddCustomHeader($header['key'], $mailer->SecureHeader($header['content']));
		}
		if (($s = $this->getEnvelopeSender()) != null) $mailer->Sender = $s;
		if (($f = $this->getFrom()) != null) {
			$mailer->SetFrom($f['email'], $f['name']);
		}
		if (($r = $this->getReplyTo()) != null) {
			$mailer->AddReplyTo($r['email'], $r['name']);
		}
		foreach ((array) $this->getRecipients() as $recipientInfo) {
			$mailer->AddAddress($recipientInfo['email'], $recipientInfo['name']);
		}
		foreach ((array) $this->getCcs() as $ccInfo) {
			$mailer->AddCC($ccInfo['email'], $ccInfo['name']);
		}
		foreach ((array) $this->getBccs() as $bccInfo) {
			$mailer->AddBCC($bccInfo['email'], $bccInfo['name']);
		}
		$mailer->Subject = $this->getSubject();
		$mailer->Body = $mailBody;
		$mailer->AltBody = PKPString::html2text($mailBody);

		$remoteAddr = $mailer->SecureHeader(Request::getRemoteAddr());
		if ($remoteAddr != '') $mailer->AddCustomHeader("X-Originating-IP: $remoteAddr");

		foreach ((array) $this->getAttachments() as $attachmentInfo) {
			$mailer->AddAttachment(
				$attachmentInfo['path'],
				$attachmentInfo['filename'],
				'base64',
				$attachmentInfo['content-type']
			);
		}

		try {
			$mailer->Send();
		} catch (phpmailerException $e) {
			error_log($mailer->ErrorInfo);
			return false;
		}
		return true;
	}

	/**
	 * Encode a display name for proper inclusion with an email address.
	 * @param $displayName string
	 * @param $send boolean True to encode the results for sending
	 * @return string
	 */
	function encodeDisplayName($displayName, $send = false) {
		if (PKPString::regexp_match('!^[-A-Za-z0-9\!#\$%&\'\*\+\/=\?\^_\`\{\|\}~]+$!', $displayName)) return $displayName;
		return ('"' . ($send ? PKPString::encode_mime_header(str_replace(
			array('"', '\\'),
			'',
			$displayName
		)) : str_replace(
			array('"', '\\'),
			'',
			$displayName
		)) . '"');
	}
}

?>
