{**
 * templates/manager/statistics/reportGenerator.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Report generator page.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.statistics.reports"}
{include file="common/header.tpl"}
{/strip}

<div class="pkp_page_content pkp_page_statistics">
    {url|assign:reportGeneratorUrl router=$smarty.const.ROUTE_COMPONENT component="statistics.ReportGeneratorHandler" op="fetchReportGenerator" escape=false}
    {load_url_in_div id="reportGeneratorContainer" url="$reportGeneratorUrl"}
</div>

{include file="common/footer.tpl"}
