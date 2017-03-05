<?php

/**
 * @defgroup pages_gateway Gateway Pages
 */
 
/**
 * @file pages/gateway/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_gateway
 * @brief Handle gateway interaction requests. 
 *
 */

switch ($op) {
	case 'index':
	case 'lockss':
	case 'clockss':
	case 'plugin':
		define('HANDLER_CLASS', 'GatewayHandler');
		import('pages.gateway.GatewayHandler');
		break;
}

?>
