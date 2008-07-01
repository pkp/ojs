<?php 

/**
 * @defgroup plugins_generic_cmsRSS
 */
 
/**
 * @file plugins/generic/cmsRSS/index.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_cmsRSS
 * @brief Wrapper for CMS RSS plugin.
 *
 */

// $Id$


require_once('CmsRssPlugin.inc.php');

return new CmsRssPlugin();

?>
