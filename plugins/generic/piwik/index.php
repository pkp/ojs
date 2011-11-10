<?php

/**
 * @defgroup plugins_generic_piwik
 */
 
/**
 * @file plugins/generic/piwik/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_piwik
 * @brief Wrapper for Piwik plugin.
 *
 */

// $Id: index.php,v 1.5 2008/07/01 01:16:13 asmecher Exp $


require_once('PiwikPlugin.inc.php');

return new PiwikPlugin();

?>
