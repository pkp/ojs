<?php

/**
 * @defgroup plugins_generic_dataverse
 */
 
/**
 * @file plugins/generic/dataverse/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_dataverse
 * @brief Wrapper for Dataverse plugin.
 *
 */

require_once('DataversePlugin.inc.php');

return new DataversePlugin();

?>
