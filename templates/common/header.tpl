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

{if !$pageTitleTranslated}{assign_translate var="pageTitleTranslated" key=$pageTitle}{/if}
{if $pageCrumbTitle}{assign_translate var="pageCrumbTitleTranslated" key=$pageCrumbTitle}{elseif !$pageCrumbTitleTranslated}{assign var="pageCrumbTitleTranslated" value=$pageTitleTranslated}{/if}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{$pageTitleTranslated}</title>
	<meta name="description" content="{$metaSearchDescription}" />
	<meta name="keywords" content="{$metaSearchKeywords}" />
	{$metaCustomHeaders}
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	{if $pageStyleSheet}
	<link rel="stylesheet" href="{$publicFilesDir}/{$pageStyleSheet.uploadName}" type="text/css" />
	{/if}
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

<div id="sidebar">
	{include file="common/sidebar.tpl"}
</div>

<div id="main">
<div id="navbar">
	<ul class="menu">
		<li><a href="{$pageUrl}">{translate key="navigation.home"}</a></li>
		<li><a href="{$pageUrl}/about">{translate key="navigation.about"}</a></li>
		{if $isUserLoggedIn}
		<li><a href="{$pageUrl}/user">{translate key="navigation.userHome"}</a></li>
		{else}
		<li><a href="{$pageUrl}/login">{translate key="navigation.login"}</a></li>
		<li><a href="{$pageUrl}/user/register">{translate key="navigation.register"}</a></li>
		{/if}
		<li><a href="{$pageUrl}/search">{translate key="navigation.search"}</a></li>
		{if $currentJournal}
		<li><a href="{$pageUrl}/issue/current">{translate key="navigation.current"}</a></li>
		<li><a href="{$pageUrl}/issue/archive">{translate key="navigation.archives"}</a></li>
		{/if}
		{foreach from=$navMenuItems item=navItem}
		<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$pageUrl}{$navItem.url|escape}{/if}">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{$pageUrl}">{translate key="navigation.home"}</a> &gt;
	{foreach from=$pageHierarchy item=hierarchyLink}
		<a href="{$pageUrl}/{$hierarchyLink[0]}" class="hierarchyLink">{if not $hierarchyLink[2]}{translate key=$hierarchyLink[1]}{else}{$hierarchyLink[1]}{/if}</a> &gt;
	{/foreach}
	<a href="{$currentUrl}" class="current">{$pageCrumbTitleTranslated}</a>
</div>

<h2>{$pageTitleTranslated}</h2>

<div id="content">
