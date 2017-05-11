<?php

/**
 * @file classes/webservice/JSONWebService.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JSONWebService
 * @ingroup webservice
 *
 * @brief A web service that returns data from JSON response.
 */


import('lib.pkp.classes.webservice.WebService');

class JSONWebService extends WebService {

	/**
	 * @see WebService::call()
	 * @param $webServiceRequest WebServiceRequest
	 * @return array The result of the web service or null in case of an error.
	 */
	function &call(&$webServiceRequest) {
		// Call the web service
		$jsonResult = parent::call($webServiceRequest);

		// Catch web service errors
		if (is_null($jsonResult)) return $jsonResult;

		$resultArray = json_decode($jsonResult, true);

		// Catch decoding errors.
		if (!is_array($resultArray)) return null;

		return $resultArray;
	}
}
?>
