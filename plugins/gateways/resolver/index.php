<?php

/**
 * @defgroup plugins_gateways_resolver Resolver Gateway Plugin
 */
 
/**
 * @file plugins/gateways/resolver/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_gateways_resolver
 * @brief Wrapper for Resolver gateway plugin.
 *
 */

require_once('ResolverPlugin.inc.php');

return new ResolverPlugin();

?>
