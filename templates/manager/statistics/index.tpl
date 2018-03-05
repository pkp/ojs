{**
 * templates/manager/statistics/index.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the statistics & reporting page.
 *
 *}
{strip}
{assign var="pageTitle" value="navigation.tools.statistics"}
{include file="common/header.tpl"}
{/strip}

<br/>

{include file="manager/statistics/statistics.tpl"}

<div class="separator">&nbsp;</div>

<br/>

<div id="reports">
<h3>{translate key="manager.statistics.reports"}</h3>
<p>{translate key="manager.statistics.reports.description"}</p>

<ul>
{foreach from=$reportPlugins key=key item=plugin}
	<li><a href="{url op="report" path=$plugin->getName()|escape}">{$plugin->getDisplayName()|escape}</a></li>
{/foreach}
</ul>
</div>
{include file="common/footer.tpl"}

