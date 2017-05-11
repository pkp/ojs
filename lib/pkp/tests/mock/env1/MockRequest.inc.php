<?php

/**
 * @file tests/mock/env1/MockRequest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup tests_mock_env1
 *
 * @brief Mock implementation of the Request class
 */


import('lib.pkp.classes.core.PKPRequest');

class Request extends PKPRequest {
	private static
		$requestMethod;

	public function setRequestMethod($requestMethod) {
		self::$requestMethod = $requestMethod;
	}

	public function isPost() {
		return (self::$requestMethod == 'POST');
	}
}
?>
