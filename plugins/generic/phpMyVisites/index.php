<?php

/**
 * @defgroup plugins_generic_phpMyVisites phpMyVisites Plugin
 */
 
/**
 * @file plugins/generic/phpMyVisites/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_phpMyVisites
 * @brief Wrapper for phpMyVisites plugin.
 *
 */

require_once('PhpMyVisitesPlugin.inc.php');

return new PhpMyVisitesPlugin();


