{**
 * templates/manager/statistics/reportGenerator.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Report generator page.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.statistics.reports"}
{assign var="pageCrumbTitle" value="manager.statistics.reports"}
{include file="common/header.tpl"}
{/strip}

{url|assign:reportGeneratorUrl router=$smarty.const.ROUTE_COMPONENT component="statistics.ReportGeneratorHandler" op="fetchReportGenerator" escape=false}
{load_url_in_div id="reportGeneratorContainer" url="$reportGeneratorUrl"}

{include file="common/footer.tpl"}
