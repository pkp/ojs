<?php

/**
 * @defgroup plugins_generic_openAds
 */

/**
 * @file plugins/generic/openAds/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2009 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_openAds
 * @brief Wrapper for OpenAds plugin.
 *
 */


require_once('OpenAdsPlugin.inc.php');

return new OpenAdsPlugin();

?>
