{**
 * templatemanagertemplate.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common sidebar(s)
 *
 *}
<div id="sidebar">
{foreach from=$rightSidebarTemplates key=name item=template}
	{if $layoutManagerPluginEdit}
		<div class="blockname">{$name}</div>
	{/if}
	{include file=$template}
{/foreach}
</div>

{if $threeColumns}
<div id="sidebar" style="float: left;">
{foreach from=$leftSidebarTemplates key=name item=template}
	{if $layoutManagerPluginEdit}
		<div class="blockname">{$name}</div>
	{/if}
	{include file=$template}
{/foreach}
</div>
{/if}