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
	
	function addAttachment($path, $file = '', $contentType = 'application/x-unknown-content-type', $disposition = 'inline') {
		if ($attachments = &$this->getData('attachments') == null) {
			$attachments = array();
		}
		
		/* If the arguments $file and $contentType are not specified,
			then try and determine them automatically. */
		if (empty($file)) {
			$file = basename($path);
			$path = substr($path, 0, strlen($path) - $strlen($file));
		}
		
		if (function_exists('mime_content_type')) {
			$contentType = mime_content_type($path.$file);
		}
		
		/* Open the file and read contents into $attachment. */
		@$fp = fopen($path.$file, 'rb');
		if($fp) {
			$attachment = '';
			while(!feof($fp)) {
				$attachment .= fread($fp, 4096);
			}
			fclose($fp);
		}
		
		if (isset($attachment)) {
			/* Encode the contents in base64. */
			$attachment = base64_encode($attachment);
			array_push($attachments, array('filename' => $file, 'content-type' => $contentType, 'disposition' => $disposition, 'content' => $attachment));
		
			return $this->setData('attachments', $attachments);
		} else {
			return 0;
		}
	}

	function getAttachments() {
		return $this->getData('attachments');
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
					array_push($tempRecipients, $recipient['name'].' <'.$recipient['email'].'>');
				}
			}
		}
		$recipients = join(', ', $tempRecipients);
		
		$from = $this->getFrom();
		$subject = $this->getSubject();
		$body = $this->getBody();
		$mimeBoundary = '==boundary_'.md5(microtime());
		
		/* Add MIME-Version and Content-Type as headers. */
		$this->addHeader('MIME-Version', '1.0');
		$this->addHeader('Content-Type', 'multipart/mixed; boundary="'.$mimeBoundary.'"');
		
		/* Add $from, $ccs, and $bccs as headers. */
		if (($from = $this->getFrom()) != null) {
			$this->addHeader('From', $from['name'].' <'.$from['email'].'>');
		}
		
		if (($ccs = $this->getCcs()) != null) {
			$tempCcs = array();
			foreach ($ccs as $cc) {
				if (Core::isWindows()) {
					array_push($tempCcs, $cc['email']);
				} else {
					array_push($tempCcs, $cc['name'].' <'.$cc['email'].'>');
				}
			}
			
			if (count($tempCcs) > 0) {
				$this->addHeader('CC', join(', ', $tempCcs));
			}
		}
		
		if (($bccs = $this->getBccs()) != null) {
			$tempBccs = array();
			foreach ($bccs as $bcc) {
				if (Core::isWindows()) {
					array_push($tempBccs, $bcc['email']);
				} else {
					array_push($tempBccs, $bcc['name'].' <'.$bcc['email'].'>');
				}
			}
			
			if (count($tempBccs) > 0) {
				$this->addHeader('BCC', join(', ', $tempBccs));
			}
		}
		
		$headers = '';
		foreach ($this->getHeaders() as $header) {
			$headers .= $header['name'].': '.$header['content'].MAIL_EOL;
		}
		$headers .= MAIL_EOL;
		
		$mailBody = 'This message is in MIME format and requires a MIME-capable mail client to view.'.MAIL_EOL.MAIL_EOL;
		$mailBody .= '--'.$mimeBoundary.MAIL_EOL;
		$mailBody .= 'Content-Type: text/plain; charset="iso-8859-1"'.MAIL_EOL.MAIL_EOL;
		$mailBody .= stripslashes($body).MAIL_EOL.MAIL_EOL;
		
		if (($attachments = $this->getAttachments()) != null) {
			foreach ($attachments as $attachment) {
				$mailBody .= '--'.$mimeBoundary.MAIL_EOL;
				$mailBody .= 'Content-Type: '.$attachment['content-type'].'; name="'.$attachment['filename'].'"'.MAIL_EOL;
				$mailBody .= 'Content-transfer-encoding: base64'.MAIL_EOL;
				$mailBody .= 'Content-disposition: '.$attachment['disposition'].MAIL_EOL.MAIL_EOL;
				$mailBody .= $attachment['content'].MAIL_EOL.MAIL_EOL;
			}
		}
	
		$mailBody .= '--'.$mimeBoundary.'--';
		
		return mail($recipients, $subject, $mailBody, $headers);
	}
}

?>
