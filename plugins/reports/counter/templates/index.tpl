{**
 * plugins/reports/counter/templates/index.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Counter plugin index
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{translate key="plugins.reports.counter"}
	</h1>

	<div class="app__contentPanel">
		<p>{translate key="plugins.reports.counter.description"}</p>
		<h2>{translate key="plugins.reports.counter.release"} {$release}</h2>
		<ul>
			{foreach from=$available key="report" item="reportfile"}
				<li>
					{translate key="plugins.reports.counter.$report.title"|lower}: {foreach from=$years item=year}&nbsp;&nbsp;<a href="{url op="reports" path="report" pluginName=$pluginName type="fetch" release=$release report=$report year=$year}">{$year|escape}</a>{/foreach}
				</li>
			{/foreach}
		</ul>

		{if $showLegacy}
		<h2>{translate key="plugins.reports.counter.olderReports"}</h2>
		<p>{translate key="plugins.reports.counter.1a.introduction"}</p>
		<ul>
			<li>{translate key="plugins.reports.counter.1a.title"}{foreach from=$years item=year}&nbsp;&nbsp;<a href="{url op="reports" path="report" pluginName=$pluginName type="report" year=$year}">{$year|escape}</a>{/foreach}</li>
			<li>{translate key="plugins.reports.counter.1a.xml"} {foreach from=$years item=year}&nbsp;&nbsp;<a href="{url op="reports" path="report" pluginName=$pluginName type="reportxml" year=$year}">{$year|escape}</a>{/foreach}</li>
		</ul>

		{if $legacyYears}
			<p>{translate key="plugins.reports.counter.legacyStats"}</p>
			<ul>
				<li>{translate key="plugins.reports.counter.1a.title"}{foreach from=$legacyYears item=year}&nbsp;&nbsp;<a href="{url op="reports" path="report" pluginName=$pluginName type="report" year=$year useOldCounterStats=true}">{$year|escape}</a>{/foreach}</li>
				<li>{translate key="plugins.reports.counter.1a.xml"} {foreach from=$legacyYears item=year}&nbsp;&nbsp;<a href="{url op="reports" path="report" pluginName=$pluginName type="reportxml" year=$year useOldCounterStats=true}">{$year|escape}</a>{/foreach}</li>
			</ul>
		{/if}
		{/if}
	</div>
{/block}