<?php

/**
 * Mail.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package mail
 *
 * Class defining basic operations for handling and sending emails.
 *
 * $Id$
 */
 
define('MAIL_EOL', Core::isWindows() ? "\r\n" : "\n");

class Mail extends DataObject {

	/**
	 * Constructor.
	 */
	function Mail() {
		parent::DataObject();
	}
	
	function addRecipient($email, $name = '') {
		if ($recipients = &$this->getData('recipients') == null) {
			$recipients = array();
		}
		array_push($recipients, array('name' => $name, 'email' => $email));
		
		return $this->setData('recipients', $recipients);
	}
	
	function getRecipients() {
		return $this->getData('recipients');
	}
	
	function addCc($email, $name = '') {
		if ($ccs = &$this->getData('ccs') == null) {
			$ccs = array();
		}
		array_push($ccs, array('name' => $name, 'email' => $email));
		
		return $this->setData('ccs', $ccs);
	}
	
	function getCcs() {
		return $this->getData('ccs');
	}

	function addBcc($email, $name = '') {
		if ($bccs = &$this->getData('bccs') == null) {
			$bccs = array();
		}
		array_push($bccs, array('name' => $name, 'email' => $email));
		
		return $this->setData('bccs', $bccs);
	}
	
	function getBccs() {
		return $this->getData('bccs');
	}
	
	function addHeader($name, $content) {
		$updated = false;
		
		if ($headers = &$this->getData('headers') == null) {
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
			$content = base64_encode($content);
			array_push($attachments, array('filename' => $fileName, 'content-type' => $contentType, 'disposition' => $contentDisposition, 'content' => $content));
		
			return $this->setData('attachments', $attachments);
		} else {
			return false;
		}
	}

	function &getAttachments() {
		return $this->getData('attachments');
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

	function send() {
		$tempRecipients = array();
		if (($recipients = $this->getRecipients()) != null) {
			foreach ($recipients as $recipient) {
				if (Core::isWindows()) {
					array_push($tempRecipients, $recipient['email']);
				} else {
					array_push($tempRecipients, String::encode_mime_header($recipient['name']).' <'.$recipient['email'].'>');
				}
			}
			$recipients = join(', ', $tempRecipients);
		} else {
			$recipients = null;
		}
		
		
		$from = $this->getFrom();
		$subject = String::encode_mime_header($this->getSubject());
		$body = $this->getBody();
		
		if (Core::isWindows()) {
			// Convert *nix-style linebreaks to DOS-style linebreaks
			$body = String::regexp_replace("/([^\r]|^)\n/", "\$1\r\n", $body);
		}
		
		if ($this->hasAttachments()) {
			// Only add MIME headers if sending an attachment
			$mimeBoundary = '==boundary_'.md5(microtime());
		
			/* Add MIME-Version and Content-Type as headers. */
			$this->addHeader('MIME-Version', '1.0');
			$this->addHeader('Content-Type', 'multipart/mixed; boundary="'.$mimeBoundary.'"');
			
		} else {
			$this->addHeader('Content-Type', 'text/plain; charset="'.Config::getVar('i18n', 'client_charset').'"');
		}
		
		/* Add $from, $ccs, and $bccs as headers. */
		if (($from = $this->getFrom()) != null) {
			$this->addHeader('From', String::encode_mime_header($from['name']).' <'.$from['email'].'>');
		}
		
		if (($ccs = $this->getCcs()) != null) {
			$tempCcs = array();
			foreach ($ccs as $cc) {
				if (Core::isWindows()) {
					array_push($tempCcs, $cc['email']);
				} else {
					array_push($tempCcs, String::encode_mime_header($cc['name']).' <'.$cc['email'].'>');
				}
			}
			
			if (count($tempCcs) > 0) {
				$this->addHeader('Cc', join(', ', $tempCcs));
			}
		}
		
		if (($bccs = $this->getBccs()) != null) {
			$tempBccs = array();
			foreach ($bccs as $bcc) {
				if (Core::isWindows()) {
					array_push($tempBccs, $bcc['email']);
				} else {
					array_push($tempBccs, String::encode_mime_header($bcc['name']).' <'.$bcc['email'].'>');
				}
			}
			
			if (count($tempBccs) > 0) {
				$this->addHeader('Bcc', join(', ', $tempBccs));
			}
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
			$mailBody .= $body.MAIL_EOL.MAIL_EOL;

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
			$mailBody = $body;
		}
		
		return String::mail($recipients, $subject, $mailBody, $headers);
	}
}

?>
