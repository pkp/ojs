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
	<title>{translate key=$pageTitle}</title>
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/default.css" type="text/css" />
	{if $journalStyleSheet}
	<link rel="stylesheet" href="{$publicDir}/{$journalStyleSheet.uploadName}" type="text/css" />
	{/if}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>

<div id="topHeader">

<div id="helpLinkDiv"><a href="javascript:openHelp('{get_help_id key="$pageId.default" url="true"}')" id="helpLink">{translate key="navigation.journalHelp"}</a></div>

{if $isUserLoggedIn}
	<div id="signOutLine">{translate key="navigation.loggedInAs" username="<b>$loggedInUsername</b>"} | <a href="{$pageUrl}/login/signOut">{translate key="navigation.signOut"}</a></div>
{/if}

<div id="siteTitle">
{if $journalLogo}
	<img src="{$publicDir}/{$journalLogo.uploadName}" alt="{$publicDir}/{$journalLogo.name}"/>
{elseif $pageHeaderTitleType !=3 && $pageLogo && !$alternateHeader}
	<img src="{$publicDir}/{$pageLogo.uploadName}" alt="{$publicDir}/{$pageLogo.name}"/>
{/if}
{if $journalHeaderTitleType==0 && $journalHeaderTitle}  
	{$journalHeaderTitle}
{elseif $journalHeaderTitleType==1 && $journalHeaderTitleImage}
	<img src="{$publicDir}/{$journalHeaderTitleImage.uploadName}" alt="{$publicDir}/{$journalHeaderTitleImage.name}"/>
{elseif $alternateHeader && $pageHeaderTitleType != 3}
	{$alternateHeader}
{elseif $pageHeaderTitleType==0 && $pageHeaderTitle}
	{$pageHeaderTitle}
{elseif $pageHeaderTitleType==1 && $pageHeaderTitleImage}
	<img src="{$publicDir}/{$pageHeaderTitleImage.uploadName}" alt="{$publicDir}/{$pageHeaderTitleImage.name}"/>
{elseif $siteTitle}
	{$siteTitle}
{else}
	{translate key="common.openJournalSystems"}
{/if}
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
	</div>
</div>
{/strip}

<div id="container">

<div id="contentFrame">

{if $enableLanguageToggle}
<div id="languageToggle"><form>{translate key="common.language"}: <select onchange="location.href='{if $languageToggleNoUser}{$currentUrl}{if strstr($currentUrl, '?')}&{else}?{/if}setLocale={else}{$pageUrl}/user/setLocale/{/if}'+this.options[this.selectedIndex].value">
{html_options options=$languageToggleLocales selected=$currentLocale}
</select></form></div>
{/if}

<div id="pageHierarchy">
<a href="{$pageUrl}" class="hierarchyLink">{translate key="navigation.home"}</a> &gt;
{foreach from=$pageHierarchy item=hierarchyLink}
<a href="{$pageUrl}/{$hierarchyLink[0]}" class="hierarchyLink">{translate key=$hierarchyLink[1]}</a> &gt;
{/foreach}

<a href="{$currentUrl}" class="hierarchyCurrent">{translate key=$pageTitle}</a>
</div>

<div id="pageTitle">{translate key=$pageTitle}<hr width="100%" /></div>
