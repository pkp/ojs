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

{assign var="pageTitle" value=$topic->getTitle()}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{translate key="help.ojsHelp"}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/help.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>
{literal}<script type="text/javascript">if (self.blur) { self.focus(); }</script>{/literal}

<div id="container">

<div id="body">

<a name="top"></a>

<div id="sidebar">
	{include file="help/toc.tpl"}
</div>

<div id="main">

<h4>{translate key="help.ojsHelp"}</h4>

<div class="thickSeparator"></div>

<div id="breadcrumb">
	<a href="{$pageUrl}/help/view/index/topic/000000">{translate key="navigation.home"}</a>
	{foreach name=breadcrumbs from=$breadcrumbs item=breadcrumb key=key}
		{if $breadcrumb != $topic->getId()}
		 &gt; <a href="{$pageUrl}/help/view/{$breadcrumb}">{$key}</a>
		{/if}
	{/foreach}
	{if $topic->getId() != "index/topic/000000"}
	&gt; <a href="{$pageUrl}/help/view/{$topic->getId()}" class="current">{$topic->getTitle()}</a>
	{/if}
</div>

<h2>{$pageTitle}</h2>

<div id="content">

<div>
	{include file="help/topic.tpl"}
</div>

{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
</div>
</div>
</div>

</div>
</body>
</html>
