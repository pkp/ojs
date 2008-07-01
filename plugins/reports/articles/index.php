<?php

/**
 * @defgroup plugins_reports_article
 */
 
/**
 * @file plugins/reports/articles/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_article
 * @brief Wrapper for article report plugin.
 *
 */

// $Id$


require_once('ArticleReportPlugin.inc.php');

return new ArticleReportPlugin();

?>
