{**
 * view.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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
		<a href="{$pageUrl}/help">{translate key="navigation.home"}</a>
		{if $topic->getId() != "index/topic/000000"}
		&gt; <a href="{get_help_id key="index.index" url="true"}">{translate key="help.ojsHelpAbbrev"}</a>
		{/if}
		{foreach name=breadcrumbs from=$breadcrumbs item=breadcrumb key=key}
			{if $breadcrumb != $topic->getId()}
			 &gt; <a href="{$pageUrl}/help/view/{$breadcrumb}">{$key}</a>
			{/if}
		{/foreach}
		&gt; <a href="{$pageUrl}/help/view/{$topic->getId()}" class="current">{$topic->getTitle()}</a>
	</div>
	
	<h2>{$topic->getTitle()}</h2>
	
	<div id="content">
		<div>{include file="help/topic.tpl"}</div>
	</div>

</div>

{include file="help/footer.tpl"}
