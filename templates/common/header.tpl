{**
 * header.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site header.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{if !$noTranslatePageTitle}{translate key=$pageTitle}{else}{$pageTitle}{/if}</title>
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/default.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/layout.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/submission.css" type="text/css" />
	{if $pageStyleSheet}
	<link rel="stylesheet" href="{$publicFilesDir}/{$pageStyleSheet.uploadName}" type="text/css" />
	{/if}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>

<div id="topHeader">
<div id="topHeaderContent">

<div id="siteTitle">
{if $pageHeaderLogo}
	<img src="{$publicFilesDir}/{$pageHeaderLogo.uploadName}" width="{$pageHeaderLogo.width}" height="{$pageHeaderLogo.height}" border="0" alt="" />
{/if}
{if $pageHeaderTitle && is_array($pageHeaderTitle)}
	<img src="{$publicFilesDir}/{$pageHeaderTitle.uploadName}" width="{$pageHeaderTitle.width}" height="{$pageHeaderTitle.height}" border="0" alt="" />
{elseif $pageHeaderTitle}
	{$pageHeaderTitle}
{elseif $alternatePageHeader}
	{$alternatePageHeader}
{elseif $siteTitle}
	{$siteTitle}
{else}
	{translate key="common.openJournalSystems"}
{/if}
</div>

</div>
</div>

{strip}
<div id="topNavMenuBg">
	<div id="tagLine"><a href="http://www.pkp.ubc.ca/ojs/" id="tagLineLink">{translate key="common.openJournalSystems"}</a></div>
	<div id="topNavMenuBar">
		<a href="{$pageUrl}" class="topNavMenu">{translate key="navigation.home"}</a>
		<a href="{$pageUrl}/about" class="topNavMenu">{translate key="navigation.about"}</a>
		{if $isUserLoggedIn}
		<a href="{$pageUrl}/user" class="topNavMenu">{translate key="navigation.userHome"}</a>
		{else}
		<a href="{$pageUrl}/login" class="topNavMenu">{translate key="navigation.login"}</a>
		<a href="{$pageUrl}/user/register" class="topNavMenu">{translate key="navigation.register"}</a>
		{/if}
		<a href="{$pageUrl}/search" class="topNavMenu">{translate key="navigation.search"}</a>
		{if $currentJournal}
		<a href="{$pageUrl}/issue/current" class="topNavMenu">{translate key="navigation.current"}</a>
		<a href="{$pageUrl}/issue/archive" class="topNavMenu">{translate key="navigation.archives"}</a>
		{/if}
		{foreach from=$navMenuItems item=navItem}
		<a href="{if $navItem.isAbsolute}{$navItem.url}{else}{$pageUrl}{$navItem.url}{/if}" class="topNavMenu">{if $navItem.isLiteral}{$navItem.name}{else}{translate key=$navItem.name}{/if}</a>
		{/foreach}
	</div>
</div>
{/strip}

<div id="container">

<div id="contentFrame">

<div id="pageHierarchy">
<a href="{$pageUrl}" class="hierarchyLink">{translate key="navigation.home"}</a> &gt;
{foreach from=$pageHierarchy item=hierarchyLink}
<a href="{$pageUrl}/{$hierarchyLink[0]}" class="hierarchyLink">{translate key=$hierarchyLink[1]}</a> &gt;
{/foreach}

{if $submissionPageHierarchy}

	<a href="{$requestPageUrl}/summary/{$pageArticleId}" class="hierarchyLink">#{$pageArticleId}</a>

	{if $parentPage}
		 &gt; <a href="{$requestPageUrl}/{$parentPage[0]}/{$pageArticleId}" class="hierarchyLink">{translate key=$parentPage[1]}</a>
	{/if}

	{if !$summaryPage}
		 &gt; <a href="{$currentUrl}" class="hierarchyCurrent">{translate key=$pageTitle}</a>
	{/if}
	</div>

	<div id="pageTitle">{if !$noTranslatePageTitle}{translate key=$pageTitle}{else}{$pageTitle}{/if}<hr width="100%" /></div>

{else}

<a href="{$currentUrl}" class="hierarchyCurrent">{if !$noTranslatePageTitle}{translate key=$pageTitle}{else}{$pageTitle}{/if}</a>
</div>
<div id="pageTitle">{if !$noTranslatePageTitle}{translate key=$pageTitle}{else}{$pageTitle}{/if}<hr width="100%" /></div>

{/if}
