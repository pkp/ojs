{**
 * header.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue page header.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{$issueTitle}{if $issue && !$issue->getPublished()} {translate key="editor.issues.preview"}{/if}</title>
	<meta name="description" content="{$metaSearchDescription}" />
	<meta name="keywords" content="{$metaSearchKeywords}" />
	{$metaCustomHeaders}
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="alternate stylesheet" title="{translate key="icon.small.alt"}" href="{$baseUrl}/styles/small.css" type="text/css" />
	<link rel="stylesheet" title="{translate key="icon.medium.alt"}" href="{$baseUrl}/styles/medium.css" type="text/css" />
	<link rel="alternate stylesheet" title="{translate key="icon.large.alt"}" href="{$baseUrl}/styles/large.css" type="text/css" />
	{foreach from=$stylesheets item=cssUrl}
	<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>
<div id="container">

<div id="header">
<div id="headerTitle">
<h1>
{if $displayPageHeaderLogo}
	<img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:"url"}" width="{$displayPageHeaderLogo.width}" height="{$displayPageHeaderLogo.height}" border="0" alt="" />
{/if}
{if $displayPageHeaderTitle && is_array($displayPageHeaderTitle)}
	<img src="{$publicFilesDir}/{$displayPageHeaderTitle.uploadName|escape:"url"}" width="{$displayPageHeaderTitle.width}" height="{$displayPageHeaderTitle.height}" border="0" alt="" />
{elseif $displayPageHeaderTitle}
	{$displayPageHeaderTitle}
{elseif $alternatePageHeader}
	{$alternatePageHeader}
{elseif $siteTitle}
	{$siteTitle}
{else}
	{translate key="common.openJournalSystems"}
{/if}
</h1>
</div>
</div>

<div id="body">

{display_template template="common/sidebar.tpl" hookname="Templates::Common::Header::sidebar"}

<div id="main">
<div id="navbar">
	<ul class="menu">
		<li><a href="{url page="index"}">{translate key="navigation.home"}</a></li>
		<li><a href="{url page="about"}">{translate key="navigation.about"}</a></li>
		{if $isUserLoggedIn}
		<li><a href="{url page="user"}">{translate key="navigation.userHome"}</a></li>
		{else}
		<li><a href="{url page="login"}">{translate key="navigation.login"}</a></li>
		<li><a href="{url page="user" op="register"}">{translate key="navigation.register"}</a></li>
		{/if}
		<li><a href="{url page="search"}">{translate key="navigation.search"}</a></li>
		{if $currentJournal}
		<li><a href="{url page="issue" op="current"}">{translate key="navigation.current"}</a></li>
		<li><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
		{/if}
		{foreach from=$navMenuItems item=navItem}
		<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{url page="index"}">{translate key="navigation.home"}</a> &gt;
	{foreach from=$pageHierarchy item=hierarchyLink}
		<a href="{$hierarchyLink[0]}" class="hierarchyLink">{if not $hierarchyLink[2]}{translate key=$hierarchyLink[1]}{else}{$hierarchyLink[1]}{/if}</a> &gt;
	{/foreach}
	<a href="{$currentUrl}" class="current">{$issueCrumbTitle}</a>
</div>

<h2>{$issueHeadingTitle}{if $issue && !$issue->getPublished()} {translate key="editor.issues.preview"}{/if}</h2>

{if $issue && $issue->getShowTitle() && $issue->getTitle() && ($issueHeadingTitle != $issue->getTitle())}
	{* If the title is specified and should be displayed then show it as a subheading *}
	<h3>{$issue->getTitle()}</h3>
{/if}

<div id="content">
