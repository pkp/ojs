{**
 * templates/management/tools/statistics.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the statistics & reporting page.
 *
 *}
<div class="pkp_page_content pkp_page_statistics">
	{help file="tools.md" section="statistics" class="pkp_help_tab"}

	{if $showMetricTypeSelector || $appSettings}
		{include file="management/tools/form/statisticsSettingsForm.tpl"}
	{/if}

	<h3>{translate key="manager.statistics.reports"}</h3>
	<p>{translate key="manager.statistics.reports.description"}</p>

	<ul>
	{foreach from=$reportPlugins key=key item=plugin}
		<li><a href="{url op="tools" path="report" pluginName=$plugin->getName()|escape}">{$plugin->getDisplayName()|escape}</a></li>
	{/foreach}
	</ul>

	<p><a class="pkp_button" href="{url op="tools" path="reportGenerator"}">{translate key="manager.statistics.reports.generateReport"}</a></p>
</div>
