<?php

/**
 * @file classes/webservice/XmlWebService.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XmlWebService
 * @ingroup webservice
 *
 * @brief A web service that returns XML data.
 */


import('lib.pkp.classes.webservice.WebService');
import('lib.pkp.classes.xslt.XSLTransformer');

class XmlWebService extends WebService {
	/** @var integer */
	var $_returnType;

	/**
	 * Constructor
	 */
	function __construct() {
		// Parent constructor intentionally not called
		if (extension_loaded('dom')) {
			$this->_returnType = XSL_TRANSFORMER_DOCTYPE_DOM;
		} else {
			$this->_returnType = XSL_TRANSFORMER_DOCTYPE_STRING;
		}
	}

	/**
	 * Get the return type
	 * @return integer
	 */
	function getReturnType() {
		return $this->_returnType;
	}

	/**
	 * Set the return type
	 * @param $returnType integer
	 */
	function setReturnType($returnType) {
		if ($returnType == XSL_TRANSFORMER_DOCTYPE_DOM) {
			if (!extension_loaded('dom')) {
				fatalError('This system does not meet minimum requirements!');
			}
		}

		$this->_returnType = $returnType;
	}


	/**
	 * @see WebService::call()
	 * @param $webServiceRequest WebServiceRequest
	 * @return DOMDocument|string the result of the web service or null in case of an error.
	 */
	function &call(&$webServiceRequest) {
		// Call the web service
		$xmlResult = parent::call($webServiceRequest);

		if (Config::getVar('debug', 'log_web_service_info')) {
			error_log('Time: ' . date('c') . "\nRequest: " . print_r($webServiceRequest, true) . "\nResponse: " . print_r($xmlResult, true) . "\nLast response status: " . $this->_lastResponseStatus . "\n");
		}
		// Catch web service errors
		if (is_null($xmlResult)) return $xmlResult;

		switch ($this->_returnType) {
			case XSL_TRANSFORMER_DOCTYPE_DOM:
				// Create DOM document
				$resultDOM = new DOMDocument('1.0', Config::getVar('i18n', 'client_charset'));
				// Try to handle non-well-formed responses
				$resultDOM->recover = true;
				$resultDOM->loadXML($xmlResult);
				return $resultDOM;

			case XSL_TRANSFORMER_DOCTYPE_STRING:
				return $xmlResult;

			default:
				assert(false);
		}
	}
}
?>
