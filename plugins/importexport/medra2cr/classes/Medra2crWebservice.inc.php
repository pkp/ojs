<?php

/**
 * @file plugins/importexport/medra2cr/classes/Medra2crWebservice.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Medra2crWebservice
 * @ingroup plugins_importexport_medra2cr_classes
 *
 * @brief A wrapper for the mEDRA2cr web service 2.0.
 *
 * NB: We do not use PHP's SoapClient because it is not PHP4 compatible and
 * it doesn't support multipart SOAP messages.
 */


import('lib.pkp.classes.xml.XMLNode');

define('MEDRA_WS_ENDPOINT_DEV', 'https://medra.dev.cineca.it/servlet/ws/medraWS');
define('MEDRA_WS_ENDPOINT', 'https://www.medra.org/servlet/ws/CRProxy');
define('MEDRA_WS_RESPONSE_OK', 200);

class Medra2crWebservice {

	/** @var string HTTP authentication credentials. */
	var $_auth;

	/** @var string The mEDRA2cr web service endpoint. */
	var $_endpoint;


	/**
	 * Constructor
	 * @param $endpoint string The mEDRA2cr web service endpoint.
	 * @param $login string
	 * @param $password string
	 */
	function Medra2crWebservice($endpoint, $login, $password) {
		$this->_endpoint = $endpoint; 
		$this->_auth = "$login:$password";
	}


	//
	// Public Web Service Actions
	//
	/**
	 * mEDRA2cr upload operation.
	 * @param $xml
	 */
	/*Funzione originale del plugin medra
	 * function upload($xml) {
		$attachmentId = $this->_getContentId('metadata');
		$attachment = array($attachmentId => $xml);
		$arg = "<med:contentID href=\"$attachmentId\" />";
		return $this->_doRequest('upload', $arg, $attachment);
	}*/	
	function upload($xml) {
		$attachmentId = $this->_getContentId('metadata'); 
		$attachment = array($attachmentId => $xml);		
		$arg="<med:accessMode>01</med:accessMode>";
		$arg.="<med:language>eng</med:language>";
		$arg.="<med:contentID>$attachmentId</med:contentID>";		
		return $this->_doRequest('deposit', $arg, $attachment); 		
	}	

	/**
	 * mEDRA2cr viewMetadata operation
	 */
   //Questa andrebbe cambiata con il metodo query descritto a nelle specifiche del ws 
   //Per il momento viene lasciata così com'è
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
		$response = curl_exec($curlCh);  // RESPONSE

		// We do not localize our error messages as they are all
		// fatal errors anyway and must be analyzed by technical staff.
		if ($response === false) {
			$result = 'OJS-mEDRA2cr: Expected string response.';
		}

		if ($result === true && ($status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE)) != MEDRA_WS_RESPONSE_OK) {
			$result = 'OJS-mEDRA2cr: Expected ' . MEDRA_WS_RESPONSE_OK . ' response code, got ' . $status . ' instead.';
		}

		curl_close($curlCh);
		
		// Check SOAP response by simple string manipulation rather
		// than instantiating a DOM.		
		if (is_string($response)) {				
			$num_errori=0;
			$pattern="#<errorsNumber>(.*?)</errorsNumber>#";
			$array_error=array();			
			String::regexp_match_get($pattern, $response, $array_error);
			if ($array_error[0]=="0"){ 
				$num_errori=0;			}
			else {
				$num_errori=(int)$array_error[1];
				$response=preg_replace("#\r\n#","", $response);			
				$description="";
				$codice="";
				$tag_error = array();				
				String::regexp_match_all("#<error>(.*?)</error>#",$response,$tag_error);
				
				$description="";
				$code="";
				$code_description=array();
				foreach ($tag_error[0] as $v)
				{					
					String::regexp_match_get('#<code>(.*?)</code>#',$v,$code);					
					String::regexp_match_get('#<description>(.*?)</description>#',$v, $description);
					$code_description[$code[1]]=$description[1];
				}
			}			
			$matches = array();
			if ($num_errori==0){
				if ($attachment) { 
					assert(String::regexp_match('#<statusCode>SUCCESS</statusCode>#', $response));
				} else {
					$parts = explode("\r\n\r\n", $response);
					$result = array_pop($parts);
					$result = String::regexp_replace('/>[^>]*$/', '>', $result);
				}
			} else {
				$string_code_description="";
				foreach($code_description as $k=>$v){
					$string_code_description.=" ".$k." ".$v." ";
				}				
				$result = 'mEDRA: ' . $status . ' - ' . $string_code_description;
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
