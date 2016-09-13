<?php 

/**
 * @defgroup plugins_generic_customLocale Custom Locale Plugin
 */
 
/**
 * @file plugins/generic/customLocale/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_customLocale
 * @brief Wrapper for custom locale plugin. Plugin based on Translator plugin.
 *
 */

require_once('CustomLocalePlugin.inc.php');

return new CustomLocalePlugin(); 

?> 
