{**
 * plugins.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
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
		<h3>{translate key="plugins.categories.$category"}</h3>
		<p>{translate key="plugins.categories.$category.description"}</p>
	{/if}
		<h4>{$plugin->getDisplayName()|escape}</h4>
		<p>
		{$plugin->getDescription()}<br/>
		{assign var=managementVerbs value=$plugin->getManagementVerbs()}
		{if $managementVerbs && $plugin->isSitePlugin() && !$isSiteAdmin}
			<i>{translate key="manager.plugins.sitePlugin"}</i>
		{elseif $managementVerbs}
			{foreach from=$managementVerbs item=verb}
				<a class="action" href="{url op="plugin" path=$category|to_array:$plugin->getName():$verb[0]}">{$verb[1]|escape}</a>&nbsp;
			{/foreach}
		{/if}
		</p>
{/foreach}

{include file="common/footer.tpl"}
