<?php

/**
 * @defgroup plugins_reports_views
 */
 
/**
 * @file plugins/reports/views/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_views
 * @brief Wrapper for view report plugin.
 *
 */

// $Id: index.php,v 1.2 2008/07/01 01:16:14 asmecher Exp $


require_once(dirname(__FILE__) . '/ViewReportPlugin.inc.php');

return new ViewReportPlugin();

?>
