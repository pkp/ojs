<?php

/**
 * Mail.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * Class defining basic operations for handling and sending emails.
 *
 * $Id$
 */
 
define('MAIL_EOL', Core::isWindows() ? "\r\n" : "\n");
define('MAIL_WRAP', 76);

class Mail extends DataObject {

	/**
	 * Constructor.
	 */
	function Mail() {
		parent::DataObject();
	}
	
	function addRecipient($email, $name = '') {
		if (($recipients = $this->getData('recipients')) == null) {
			$recipients = array();
		}
		array_push($recipients, array('name' => $name, 'email' => $email));
		
		return $this->setData('recipients', $recipients);
	}

	function setEnvelopeSender($envelopeSender) {
		$this->setData('envelopeSender', $envelopeSender);
	}

	function getEnvelopeSender() {
		return $this->getData('envelopeSender');
	}

	function getContentType() {
		return $this->getData('content_type');
	}
	
	function setContentType($contentType) {
		return $this->setData('content_type', $contentType);
	}
	
	function getRecipients() {
		return $this->getData('recipients');
	}
	
	function setRecipients(&$recipients) {
		return $this->setData('recipients', $recipients);
	}
	
	function addCc($email, $name = '') {
		if (($ccs = $this->getData('ccs')) == null) {
			$ccs = array();
		}
		array_push($ccs, array('name' => $name, 'email' => $email));
		
		return $this->setData('ccs', $ccs);
	}
	
	function getCcs() {
		return $this->getData('ccs');
	}

	function setCcs(&$ccs) {
		return $this->setData('ccs', $ccs);
	}
	
	function addBcc($email, $name = '') {
		if (($bccs = $this->getData('bccs')) == null) {
			$bccs = array();
		}
		array_push($bccs, array('name' => $name, 'email' => $email));
		
		return $this->setData('bccs', $bccs);
	}
	
	function getBccs() {
		return $this->getData('bccs');
	}
	
	function setBccs(&$bccs) {
		return $this->setData('bccs', $bccs);
	}
	
	function addHeader($name, $content) {
		$updated = false;
		
		if (($headers = $this->getData('headers')) == null) {
			$headers = array();
		}

		foreach ($headers as $key => $value) {
			if ($headers[$key]['name'] == $name) {
				$headers[$key]['content'] = $content;
				$updated = true;
			}
		}
			
		if (!$updated) {
			array_push($headers, array('name' => $name,'content' => $content));
		}
			
		return $this->setData('headers', $headers);
	}
	
	function getHeaders() {
		return $this->getData('headers');
	}
	
	function setHeaders(&$headers) {
		return $this->setData('headers', $headers);
	}
	
	/**
	 * Adds a file attachment to the email.
	 * @param $filePath string complete path to the file to attach
	 * @param $fileName string attachment file name (optional)
	 * @param $contentType string attachment content type (optional)
	 * @param $contentDisposition string attachment content disposition, inline or attachment (optional, default attachment)
	 */
	function addAttachment($filePath, $fileName = '', $contentType = '', $contentDisposition = 'attachment') {
		if ($attachments = &$this->getData('attachments') == null) {
			$attachments = array();
		}
		
		/* If the arguments $fileName and $contentType are not specified,
			then try and determine them automatically. */
		if (empty($fileName)) {
			$fileName = basename($filePath);
		}
		
		if (empty($contentType)) {
			if (function_exists('mime_content_type')) {
				$contentType = mime_content_type($filePath);
			} else {
				$contentType = 'application/x-unknown-content-type';
			}
		}
		
		// Open the file and read contents into $attachment
		if (is_readable($filePath) && is_file($filePath)) {
			$fp = fopen($filePath, 'rb');
			if ($fp) {
				$content = '';
				while (!feof($fp)) {
					$content .= fread($fp, 4096);
				}
				fclose($fp);
			}
		}
		
		if (isset($content)) {
			/* Encode the contents in base64. */
			$content = chunk_split(base64_encode($content), MAIL_WRAP, MAIL_EOL);
			array_push($attachments, array('filename' => $fileName, 'content-type' => $contentType, 'disposition' => $contentDisposition, 'content' => $content));
		
			return $this->setData('attachments', $attachments);
		} else {
			return false;
		}
	}

	function &getAttachments() {
		$attachments = &$this->getData('attachments');
		return $attachments;
	}
	
	function hasAttachments() {
		$attachments = &$this->getAttachments();
		return ($attachments != null && count($attachments) != 0);
	}

	function setFrom($email, $name = '') {
		return $this->setData('from', array('name' => $name, 'email' => $email));
	}
	
	function getFrom() {
		return $this->getData('from');
	}
	
	function setSubject($subject) {
		return $this->setData('subject', $subject);
	}

	function getSubject() {
		return $this->getData('subject');
	}

	function setBody($body) {
		return $this->setData('body', $body);
	}
	
	function getBody() {
		return $this->getData('body');
	}
	
	/**
	 * Return a string containing the from address.
	 * @param $encode boolean encode the data (e.g., when sending)
	 * @return string
	 */
	function getFromString($encode = true) {
		$from = $this->getFrom();
		if ($from == null) {
			return null;
		} else {
			return ($encode ? String::encode_mime_header($from['name']) : $from['name']) . ' <'.$from['email'].'>';
		}
	}
	
	/**
	 * Return a string from an array of (name, email) pairs.
	 * @param $encode boolean
	 * @return string;
	 */
	function getAddressArrayString($addresses, $encode = true) {
		if ($addresses == null) {
			return null;
			
		} else {
			$addressString = '';
			
			foreach ($addresses as $address) {
				if (!empty($addressString)) {
					$addressString .= ', ';
				}
				
				if (Core::isWindows()) {
					$addressString .= $address['email'];
					
				} else {
					$addressString .= ($encode ? String::encode_mime_header($address['name']) : $address['name']) . ' <'.$address['email'].'>';
				}
			}
			
			return $addressString;
		}
	}
	
	/**
	 * Return a string containing the recipients.
	 * @param $encode boolean
	 * @return string
	 */
	function getRecipientString($encode = true) {
		return $this->getAddressArrayString($this->getRecipients(), $encode);
	}
	
	/**
	 * Return a string containing the Cc recipients.
	 * @param $encode boolean
	 * @return string
	 */
	function getCcString($encode = true) {
		return $this->getAddressArrayString($this->getCcs(), $encode);
	}
	
	/**
	 * Return a string containing the Bcc recipients.
	 * @param $encode boolean
	 * @return string
	 */
	function getBccString($encode = true) {
		return $this->getAddressArrayString($this->getBccs(), $encode);
	}
	

	/**
	 * Send the email.
	 * @return boolean
	 */
	function send() {
		$recipients = $this->getRecipientString();
		$from = $this->getFromString();
		$subject = String::encode_mime_header($this->getSubject());
		$body = $this->getBody();
		
		// FIXME Some *nix mailers won't work with CRLFs
		if (Core::isWindows()) {
			// Convert LFs to CRLFs for Windows
			$body = String::regexp_replace("/([^\r]|^)\n/", "\$1\r\n", $body);
		} else {
			// Convert CRLFs to LFs for *nix
			$body = String::regexp_replace("/\r\n/", "\n", $body);
		}

		if ($this->getContentType() != null) {
			$this->addHeader('Content-Type', $this->getContentType());
		} elseif ($this->hasAttachments()) {
			// Only add MIME headers if sending an attachment
			$mimeBoundary = '==boundary_'.md5(microtime());
		
			/* Add MIME-Version and Content-Type as headers. */
			$this->addHeader('MIME-Version', '1.0');
			$this->addHeader('Content-Type', 'multipart/mixed; boundary="'.$mimeBoundary.'"');
		
		} else {
			$this->addHeader('Content-Type', 'text/plain; charset="'.Config::getVar('i18n', 'client_charset').'"');
		}
		
		$this->addHeader('X-Mailer', 'Open Journal Systems v2');
		$this->addHeader('X-Originating-IP', Request::getRemoteAddr());
		
		/* Add $from, $ccs, and $bccs as headers. */
		if ($from != null) {
			$this->addHeader('From', $from);
		}
		
		$ccs = $this->getCcString();
		if ($ccs != null) {
			$this->addHeader('Cc', $ccs);
		}
		
		$bccs = $this->getBccString();
		if ($bccs != null) {
			$this->addHeader('Bcc', $bccs);
		}
		
		$headers = '';
		foreach ($this->getHeaders() as $header) {
			if (!empty($headers)) {
				$headers .= MAIL_EOL;
			}
			$headers .= $header['name'].': '.$header['content'];
		}
		
		if ($this->hasAttachments()) {
			// Add the body
			$mailBody = 'This message is in MIME format and requires a MIME-capable mail client to view.'.MAIL_EOL.MAIL_EOL;
			$mailBody .= '--'.$mimeBoundary.MAIL_EOL;
			$mailBody .= sprintf('Content-Type: text/plain; charset=%s', Config::getVar('i18n', 'client_charset')) . MAIL_EOL.MAIL_EOL;
			$mailBody .= wordwrap($body, MAIL_WRAP, MAIL_EOL).MAIL_EOL.MAIL_EOL;

			// Add the attachments
			$attachments = $this->getAttachments();
			foreach ($attachments as $attachment) {
				$mailBody .= '--'.$mimeBoundary.MAIL_EOL;
				$mailBody .= 'Content-Type: '.$attachment['content-type'].'; name="'.$attachment['filename'].'"'.MAIL_EOL;
				$mailBody .= 'Content-transfer-encoding: base64'.MAIL_EOL;
				$mailBody .= 'Content-disposition: '.$attachment['disposition'].MAIL_EOL.MAIL_EOL;
				$mailBody .= $attachment['content'].MAIL_EOL.MAIL_EOL;
			}
			
			$mailBody .= '--'.$mimeBoundary.'--';
		
		} else {
			// Just add the body
			$mailBody = wordwrap($body, MAIL_WRAP, MAIL_EOL);
		}

		if ($this->getEnvelopeSender() != null) {
			$additionalParameters = '-f ' . $this->getEnvelopeSender();
		} else {
			$additionalParameters = null;
		}
		
		if (Config::getVar('email', 'smtp')) {
			static $smtp = null;
			if (!isset($smtp)) {
				import('mail.SMTPMailer');
				$smtp = &new SMTPMailer();
			}
			return $smtp->mail($this, $recipients, $subject, $mailBody, $headers);
		} else {
			return String::mail($recipients, $subject, $mailBody, $headers, $additionalParameters);
		}
	}
}

?>
