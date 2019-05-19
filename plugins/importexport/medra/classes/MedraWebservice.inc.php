<?php

/**
 * @file plugins/importexport/medra/classes/MedraWebservice.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraWebservice
 * @ingroup plugins_importexport_medra_classes
 *
 * @brief A wrapper for the mEDRA web service 2.0.
 *
 * NB: We do not use PHP's SoapClient because it is not PHP4 compatible and
 * it doesn't support multipart SOAP messages.
 */


import('lib.pkp.classes.xml.XMLNode');

define('MEDRA_WS_ENDPOINT_DEV', 'https://medra.dev.cineca.it/servlet/ws/medraWS');
define('MEDRA_WS_ENDPOINT', 'https://www.medra.org/servlet/ws/medraWS');
define('MEDRA_WS_RESPONSE_OK', 200);

class MedraWebservice {

	/** @var string HTTP authentication credentials. */
	var $_auth;

	/** @var string The mEDRA web service endpoint. */
	var $_endpoint;


	/**
	 * Constructor
	 * @param $endpoint string The mEDRA web service endpoint.
	 * @param $login string
	 * @param $password string
	 */
	function __construct($endpoint, $login, $password) {
		$this->_endpoint = $endpoint;
		$this->_auth = "$login:$password";
	}


	//
	// Public Web Service Actions
	//
	/**
	 * mEDRA upload operation.
	 * @param $xml
	 */
	function upload($xml) {
		$attachmentId = $this->_getContentId('metadata');
		$attachment = array($attachmentId => $xml);
		$arg = "<med:contentID href=\"$attachmentId\" />";
		return $this->_doRequest('upload', $arg, $attachment);
	}

	/**
	 * mEDRA viewMetadata operation
	 */
	function viewMetadata($doi) {
		$doi = $this->_escapeXmlEntities($doi);
		$arg = "<med:doi>$doi</med:doi>";
		return $this->_doRequest('viewMetadata', $arg);
	}


	//
	// Internal helper methods.
	//
	/**
	 * Do the actual web service request.
	 * @param $action string
	 * @param $arg string
	 * @param $attachment array
	 * @return boolean|string True for success, an error message otherwise.
	 */
	function _doRequest($action, $arg, $attachment = null) {
		// Build the multipart SOAP message from scratch.
		$soapMessage =
			'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" ' .
					'xmlns:med="http://www.medra.org">' .
				'<SOAP-ENV:Header/>' .
				'<SOAP-ENV:Body>' .
					"<med:$action>$arg</med:$action>" .
				'</SOAP-ENV:Body>' .
			'</SOAP-ENV:Envelope>';

		$soapMessageId = $this->_getContentId($action);
		if ($attachment) {
			assert(count($attachment) == 1);
			$request =
				"--MIME_boundary\r\n" .
				$this->_getMimePart($soapMessageId, $soapMessage) .
				"--MIME_boundary\r\n" .
				$this->_getMimePart(key($attachment), current($attachment)) .
				"--MIME_boundary--\r\n";
			$contentType = 'multipart/related; type="text/xml"; boundary="MIME_boundary"';
		} else {
			$request = $soapMessage;
			$contentType = 'text/xml';
		}

		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		// Set up basic authentication.
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, $this->_auth);

		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);

		// Make SOAP request.
		curl_setopt($curlCh, CURLOPT_URL, $this->_endpoint);
		$extraHeaders = array(
			'SOAPAction: "' . $action . '"',
			'Content-Type: ' . $contentType,
			'UserAgent: OJS-mEDRA'
		);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $extraHeaders);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $request);

		$result = true;
		$response = curl_exec($curlCh);

		// We do not localize our error messages as they are all
		// fatal errors anyway and must be analyzed by technical staff.
		if ($response === false) {
			$result = 'OJS-mEDRA: Expected string response.';
		}

		if ($result === true && ($status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE)) != MEDRA_WS_RESPONSE_OK) {
			$result = 'OJS-mEDRA: Expected ' . MEDRA_WS_RESPONSE_OK . ' response code, got ' . $status . ' instead.';
		}

		curl_close($curlCh);

		// Check SOAP response by simple string manipulation rather
		// than instantiating a DOM.
		if (is_string($response)) {
			$matches = array();
			PKPString::regexp_match_get('#<faultstring>([^<]*)</faultstring>#', $response, $matches);
			if (empty($matches)) {
				if ($attachment) {
					assert(PKPString::regexp_match('#<returnCode>success</returnCode>#', $response));
				} else {
					$parts = explode("\r\n\r\n", $response);
					$result = array_pop($parts);
					$result = PKPString::regexp_replace('/>[^>]*$/', '>', $result);
				}
			} else {
				$result = 'mEDRA: ' . $status . ' - ' . $matches[1];
			}
		} else {
			$result = 'OJS-mEDRA: Expected string response.';
		}

		return $result;
	}

	/**
	 * Create a mime part with the given content.
	 * @param $contentId string
	 * @param $content string
	 * @return string
	 */
	function _getMimePart($contentId, $content) {
		return
			"Content-Type: text/xml; charset=utf-8\r\n" .
			"Content-ID: <${contentId}>\r\n" .
			"\r\n" .
			$content . "\r\n";
	}

	/**
	 * Create a globally unique MIME content ID.
	 * @param $prefix string
	 * @return string
	 */
	function _getContentId($prefix) {
		return $prefix . md5(uniqid()) . '@medra.org';
	}

	/**
	 * Escape XML entities.
	 * @param $string string
	 */
	function _escapeXmlEntities($string) {
		return XMLNode::xmlentities($string);
	}
}
