<?php

/**
 * @file classes/form/validation/FormValidatorReCaptcha.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorReCaptcha
 * @ingroup form_validation
 *
 * @brief Form validation check reCaptcha values.
 */

define('RECAPTCHA_RESPONSE_FIELD', 'g-recaptcha-response');
define('RECAPTCHA_HOST', 'https://www.google.com');
define("RECAPTCHA_PATH", "/recaptcha/api/siteverify");

class FormValidatorReCaptcha extends FormValidator {
	/** @var string */
	var $_userIp;

	/**
	 * Constructor.
	 * @param $form object
	 * @param $userIp string IP address of user request
	 * @param $message string Key of message to display on mismatch
	 */
	function __construct(&$form, $userIp, $message) {
		parent::__construct($form, RECAPTCHA_RESPONSE_FIELD, FORM_VALIDATOR_REQUIRED_VALUE, $message);
		$this->_userIp = $userIp;
	}


	//
	// Public methods
	//
	/**
	 * @see FormValidator::isValid()
	 * Determine whether or not the form meets this ReCaptcha constraint.
	 * @return boolean
	 */
	function isValid() {

		$privateKey = Config::getVar('captcha', 'recaptcha_private_key');
		if (is_null($privateKey) || empty($privateKey)) {
			return false;
		}

		if (is_null($this->_userIp) || empty($this->_userIp)) {
			return false;
		}

		$form =& $this->getForm();

		// Request response from recaptcha api
		$requestOptions = array(
			'http' => array(
				'header' => "Content-Type: application/x-www-form-urlencoded;\r\n",
				'method' => 'POST',
				'content' => http_build_query(array(
					'secret' => $privateKey,
					'response' => $form->getData(RECAPTCHA_RESPONSE_FIELD),
					'remoteip' => $this->_userIp,
				)),
			),
		);

		$requestContext = stream_context_create($requestOptions);
		$response = file_get_contents(RECAPTCHA_HOST . RECAPTCHA_PATH, false, $requestContext);
		if ($response === false) {
			return false;
		}

		$response = json_decode($response, true);

		// Unrecognizable response from Google server
		if (isset($response['success']) && $response['success'] === true) {
			return true;
		} else {
			if (isset($response['error-codes']) && is_array($response['error-codes'])) {
				$this->_message = 'common.captcha.error.' . $response['error-codes'][0];
			}
			return false;
		}

	}
}


?>
