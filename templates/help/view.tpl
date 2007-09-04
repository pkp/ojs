{**
 * view.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a help topic.
 *
 * $Id$
 *}
{include file="help/header.tpl"}

<div id="sidebar">
	{include file="help/toc.tpl"}
</div>

<div id="main">

	<h4>{translate key="help.ojsHelp"}</h4>
	
	<div class="thickSeparator"></div>
	
	<div id="breadcrumb">
		{if $topic->getId() == "index/topic/000000"}
			<a href="{get_help_id key="index.index" url="true"}" class="current">{translate key="navigation.home"}</a>
		{else}
			<a href="{get_help_id key="index.index" url="true"}">{translate key="navigation.home"}</a>
			{foreach name=breadcrumbs from=$breadcrumbs item=breadcrumb key=key}
				{if $breadcrumb != $topic->getId()}
				 &gt; <a href="{url op="view" path=$breadcrumb|explode:"/"}">{$key}</a>
				{/if}
			{/foreach}		
			&gt; <a href="{url op="view" path=$topic->getId()|explode:"/"}" class="current">{$topic->getTitle()}</a>
		{/if}
	</div>
	
	<h2>{$topic->getTitle()}</h2>
	
	<div id="content">
		<div>{include file="help/topic.tpl"}</div>
	</div>

</div>

{include file="help/footer.tpl"}
