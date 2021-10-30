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
	</div>
{/block}