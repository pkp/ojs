<?php

/**
 * @defgroup metsGatewayPlugin METS Gateway Plugin
 * Implements the METS Gateway Plugin.
 */
 
/**
 * @file plugins/gateways/metsGateway/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup metsGatewayPlugin
 * @brief Wrapper for Journal Export gateway plugin.
 *
 */

require_once('MetsGatewayPlugin.inc.php');

return new METSGatewayPlugin();

?>
