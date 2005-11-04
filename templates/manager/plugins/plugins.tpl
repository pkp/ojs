{**
 * plugins.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List available import/export plugins.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.plugins.pluginManagement"}
{include file="common/header.tpl"}

{foreach from=$plugins item=plugin}
	{if $plugin->getCategory() != $category}
		{assign var=category value=$plugin->getCategory()}
		{if $notFirst}</ul>{/if}
		<h3>{translate key="plugins.categories.$category"}</h3>
		<p>{translate key="plugins.categories.$category.description"}</p>
		<ul>
		{assign var=notFirst value=1}
	{/if}
	<li>
		<strong>{$plugin->getDisplayName()|escape}</strong>:&nbsp;{$plugin->getDescription()|escape}<br/>
		{assign var=managementVerbs value=$plugin->getManagementVerbs()}
		{if $managementVerbs && $plugin->isSitePlugin() && !$isSiteAdmin}
			<i>{translate key="manager.plugins.sitePlugin"}</i>
		{elseif $managementVerbs}
			{foreach from=$managementVerbs item=verb}
				<a class="action" href="plugin/{$category}/{$plugin->getName()}/{$verb[0]}">{$verb[1]|escape}</a>&nbsp;
			{/foreach}
		{/if}
	</li>
{/foreach}
{if $notFirst}</ul>{/if}

{include file="common/footer.tpl"}
