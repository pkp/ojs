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
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>

<div id="helpLinkDiv"><a href="javascript:openHelp('{$indexUrl}/index/help/view/{if $helpId}{$helpId}{else}000000{/if}')" id="helpLink">{translate key="navigation.journalHelp"}</a></div>

{if $isUserLoggedIn}
	<div id="signOutLine">{translate key="navigation.loggedInAs" username="<b>$loggedInUsername</b>"} | <a href="{$pageUrl}/login/signOut" id="signOutLine">{translate key="navigation.signOut"}</a></div>
{/if}

<div id="siteTitle">{if $siteTitle}{$siteTitle}{else}{translate key="common.openJournalSystems"}{/if}</div>

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
		{if $currentJournal}
			<a href="{$pageUrl}/search" class="topNavMenu">{translate key="navigation.current"}</a>
			<a href="{$pageUrl}/search" class="topNavMenu">{translate key="navigation.archives"}</a>
			{foreach name=navItems from=$navItems key=navItemId item=navItem}
					{if !$smarty.foreach.navItems.last}
						<a href="{$pageUrl}{$navItem.url}" class="topNavMenu">{$navItem.name}</a>
					{/if}
			{/foreach}
		{/if}
			
		<a href="{$pageUrl}/search" class="topNavMenu">{translate key="navigation.search"}</a>
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

<a href="{$currentUrl}" class="hierarchyCurrent">{translate key=$pageTitle}</a>
</div>

<div id="pageTitle">{translate key=$pageTitle}<hr width="100%" /></div>
