<?php

/**
 * @defgroup plugins_generic_openAds
 */
 
/**
 * @file plugins/generic/openAds/index.php
 *
 * Copyright (c) 2003-2008 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_openAds
 * @brief Wrapper for OpenAds plugin.
 *
 */

// $Id: index.php,v 1.0 2006/10/20 12:27pm

require_once('OpenAdsPlugin.inc.php');

return new OpenAdsPlugin();

?>
