{**
 * searchResults.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show help search results.
 *
 * $Id$
 *}

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

<div id="body" style="width: 630px">

<a name="top"></a>

<div id="main" style="width: 630px">

<h4>{translate key="help.ojsHelp"}</h4>

<div class="thickSeparator"></div>

<div id="breadcrumb">
	<a href="{$pageUrl}/help/view/index/topic/000000">{translate key="navigation.home"}</a>
	{foreach name=breadcrumbs from=$breadcrumbs item=breadcrumb key=key}
		 &gt; <a href="{$pageUrl}/help/view/{$breadcrumb}" class="{if $smarty.foreach.breadcrumbs.last}current{else}hierarchyLink{/if}">{$key}</a>
	{/foreach}
</div>

<h2>{$pageTitle}</h2>

<div id="content">

<div style="padding-top: 1.5em;">
	<form action="{$pageUrl}/help/search" method="post" style="display: inline">
	{translate key="navigation.search"}&nbsp;&nbsp;<input type="text" name="keyword" size="16" maxlength="60" value="{$helpSearchKeyword}" class="textField" />
	</form>
</div>

<br />

<div id="search">

{if count($topics) > 0}
	<b>{translate key="help.matchesFound" matches=$topics|@count}</b>
	<ul>
	{foreach name=results from=$topics item=topic}
		<li><a href="{$pageUrl}/help/view/{$topic->getId()}">{$topic->getTitle()}</a></li>
	{/foreach}
	</ul>
{else}
	{translate key="help.noMatchingTopics"}
{/if}

</div>

<script type="text/javascript">document.forms[0].keyword.focus()</script>

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
