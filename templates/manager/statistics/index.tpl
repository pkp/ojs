{**
 * index.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the statistics & reporting page.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.statistics"}
{include file="common/header.tpl"}
{/strip}

<br/>

{include file="manager/statistics/statistics.tpl"}

<div class="separator">&nbsp;</div>

<br/>

<a name="statistics"></a>
<h3>{translate key="manager.statistics.reports"}</h3>
<p>{translate key="manager.statistics.reports.description"}</p>

<ul class="plain">
{foreach from=$reportPlugins key=key item=plugin}
	<li>&#187; <a href="{url op="report" path=$plugin->getName()|escape}">{$plugin->getDisplayName()|escape}</a></li>
{/foreach}
</ul>

{include file="common/footer.tpl"}
