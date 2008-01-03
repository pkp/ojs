{**
 * header.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *
 * $Id$
 *}
{if !$pageTitleTranslated}{translate|assign:"pageTitleTranslated" key=$pageTitle}{/if}
{if $pageCrumbTitle}{translate|assign:"pageCrumbTitleTranslated" key=$pageCrumbTitle}{elseif !$pageCrumbTitleTranslated}{assign var="pageCrumbTitleTranslated" value=$pageTitleTranslated}{/if}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<title>{$pageTitleTranslated}</title>
	<meta name="description" content="{$metaSearchDescription|escape}" />
	<meta name="keywords" content="{$metaSearchKeywords|escape}" />
	{$metaCustomHeaders}

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />

	{call_hook|assign:"leftSidebarCode" name="Templates::Common::LeftSidebar"}
	{call_hook|assign:"rightSidebarCode" name="Templates::Common::RightSidebar"}
	{if $leftSidebarCode || $rightSidebarCode}<link rel="stylesheet" href="{$baseUrl}/styles/sidebar.css" type="text/css" />{/if}
	{if $leftSidebarCode}<link rel="stylesheet" href="{$baseUrl}/styles/leftSidebar.css" type="text/css" />{/if}
	{if $rightSidebarCode}<link rel="stylesheet" href="{$baseUrl}/styles/rightSidebar.css" type="text/css" />{/if}
	{if $leftSidebarCode && $rightSidebarCode}<link rel="stylesheet" href="{$baseUrl}/styles/bothSidebars.css" type="text/css" />{/if}

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
	<img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:"url"}" width="{$displayPageHeaderLogo.width|escape}" height="{$displayPageHeaderLogo.height|escape}" style="border: 0;" alt="" />
{/if}
{if $displayPageHeaderTitle && is_array($displayPageHeaderTitle)}
	<img src="{$publicFilesDir}/{$displayPageHeaderTitle.uploadName|escape:"url"}" width="{$displayPageHeaderTitle.width|escape}" height="{$displayPageHeaderTitle.height|escape}" style="border: 0;" alt="" />
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

{if $leftSidebarCode || $rightSidebarCode}
	<div id="sidebar">
		{if $leftSidebarCode}
			<div id="leftSidebar">
				{$leftSidebarCode}
			</div>
		{/if}
		{if $rightSidebarCode}
			<div id="rightSidebar">
				{$rightSidebarCode}
			</div>
		{/if}
	</div>
{/if}

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
		{if $enableAnnouncements}
		<li><a href="{url page="announcement"}">{translate key="announcement.announcements"}</a></li>
		{/if}
		{call_hook name="Templates::Common::Header::Navbar::CurrentJournal"}
		{/if}
		{foreach from=$navMenuItems item=navItem}
		<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{url page="index"}">{translate key="navigation.home"}</a> &gt;
	{foreach from=$pageHierarchy item=hierarchyLink}
		<a href="{$hierarchyLink[0]|escape}" class="hierarchyLink">{if not $hierarchyLink[2]}{translate key=$hierarchyLink[1]}{else}{$hierarchyLink[1]|escape}{/if}</a> &gt;
	{/foreach}
	<a href="{$currentUrl|escape}" class="current">{$pageCrumbTitleTranslated}</a>
</div>

<h2>{$pageTitleTranslated}</h2>

{if $pageSubtitle && !$pageSubtitleTranslated}{translate|assign:"pageSubtitleTranslated" key=$pageSubtitle}{/if}
{if $pageSubtitleTranslated}
	<h3>{$pageSubtitleTranslated}</h3>
{/if}

<div id="content">
