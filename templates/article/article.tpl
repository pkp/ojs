{**
 * article.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{$issue->getFirstAuthor(true)}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>

<div id="container">

<div id="body">

<div id="main">

<div id="navbar">
	<ul class="menu">
		<li><a href="{$pageUrl}" target="_parent">{translate key="navigation.home"}</a></li>
		<li><a href="{$pageUrl}/about" target="_parent">{translate key="navigation.about"}</a></li>
		{if $isUserLoggedIn}
		<li><a href="{$pageUrl}/user" target="_parent">{translate key="navigation.userHome"}</a></li>
		{else}
		<li><a href="{$pageUrl}/login" target="_parent">{translate key="navigation.login"}</a></li>
		<li><a href="{$pageUrl}/user/register" target="_parent">{translate key="navigation.register"}</a></li>
		{/if}
		<li><a href="{$pageUrl}/search" target="_parent">{translate key="navigation.search"}</a></li>
		{if $currentJournal}
		<li><a href="{$pageUrl}/issue/current" target="_parent">{translate key="navigation.current"}</a></li>
		<li><a href="{$pageUrl}/issue/archive" target="_parent">{translate key="navigation.archives"}</a></li>
		{/if}
		{foreach from=$navMenuItems item=navItem}
		<li><a href="{if $navItem.isAbsolute}{$navItem.url}{else}{$pageUrl}{$navItem.url}{/if}" target="_parent">{if $navItem.isLiteral}{$navItem.name}{else}{translate key=$navItem.name}{/if}</a></li>
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{$pageUrl}" target="_parent">{translate key="navigation.home"}</a> &gt;
	<a href="{$pageUrl}/issue/view/{$issue->getIssueId()}" target="_parent">{$issue->getIssueIdentification(false,true)}</a> &gt;
	<a href="{$pageUrl}/article/view/{$articleId}/{$galleyId}" class="current" target="_parent">{$issue->getFirstAuthor(true)}</a>
</div>

<div id="content">

{$galley->getHTMLContents("$requestPageUrl/viewFile")}

</div>

</div>
</div>
</div>

</body>
</html>
