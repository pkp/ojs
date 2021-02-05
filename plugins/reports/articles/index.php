<?php

/**
 * @defgroup plugins_reports_article Article Report Plugin
 */
 
/**
 * @file plugins/reports/articles/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_article
 * @brief Wrapper for article report plugin.
 *
 */

require_once('ArticleReportPlugin.inc.php');

return new ArticleReportPlugin();


