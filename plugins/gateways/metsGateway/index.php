<?php

/**
 * @defgroup plugins
 */
 
/**
 * @file plugins/gateways/metsGateway/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins
 * @brief Wrapper for Journal Export gateway plugin.
 *
 */

require_once('MetsGatewayPlugin.inc.php');

return new METSGatewayPlugin();

?>
