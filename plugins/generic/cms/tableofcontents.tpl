{**
 * tableofcontents.tpl
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Table of Contents for the CMS Plugin
 *
 *}
<div class="block">
{if $cmsPluginToc}
{*	<span class="blockTitle">{translate key="plugins.generic.cms.toc"}</span>*}
	{if $cmsPluginEdit}
		{foreach from=$cmsPluginToc item=item}
			{if $item[0] eq 1}
				<a style="margin-left:0px" href="{url page="manager" op="plugin" path="generic"}/CmsPlugin/edit/{$item[1]}">{$item[2]}</a><br />
			{elseif $item[0] eq 2}
				<a style="margin-left:15px" href="{url page="manager" op="plugin" path="generic"}/CmsPlugin/edit/{$item[1]}">{$item[2]}</a><br />
			{elseif $item[0] eq 3}
				<a style="margin-left:30px" href="{url page="manager" op="plugin" path="generic"}/CmsPlugin/edit/{$item[1]}">{$item[2]}</a><br />	
			{/if}
		{/foreach}
	{else}
		{foreach from=$cmsPluginToc item=item}
			{if $item[0] eq 1}
				<a style="margin-left:0px" href="{url page="cms"}/view/{$item[1]}">{$item[2]}</a><br />
			{elseif $item[0] eq 2}
				<a style="margin-left:15px" href="{url page="cms"}/view/{$item[1]}">{$item[2]}</a><br />
			{elseif $item[0] eq 3}
				<a style="margin-left:30px" href="{url page="cms"}/view/{$item[1]}">{$item[2]}</a><br />	
			{/if}
		{/foreach}
	{/if}
{/if}
</div>
