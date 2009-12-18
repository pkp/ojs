{**
 * header.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View -- Header component.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$article->getLocalizedTitle()|escape} | {$article->getFirstAuthor(true)|escape} | {$currentJournal->getLocalizedTitle()|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="description" content="{$article->getLocalizedTitle()|escape}" />
	{if $article->getLocalizedSubject()}
		<meta name="keywords" content="{$article->getLocalizedSubject()|escape}" />
	{/if}

	{include file="article/dublincore.tpl"}
	{include file="article/googlescholar.tpl"}

	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/lib/pkp/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>

<div id="container">

<div id="body">

<div id="main">

<h2>{$siteTitle|escape}{if $issue},&nbsp;{$issue->getIssueIdentification(false,true)|escape}{/if}</h2>

<div id="navbar">
	<ul class="menu">
		<li><a href="{url page="index"}" target="_parent">{translate key="navigation.home"}</a></li>
		<li><a href="{url page="about"}" target="_parent">{translate key="navigation.about"}</a></li>

		{if $isUserLoggedIn}
			<li><a href="{url journal="index" page="user"}" target="_parent">{translate key="navigation.userHome"}</a></li>
		{else}
			<li><a href="{url page="login"}" target="_parent">{translate key="navigation.login"}</a></li>
			<li><a href="{url page="user" op="register"}" target="_parent">{translate key="navigation.register"}</a></li>
		{/if}{* $isUserLoggedIn *}

		{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li><a href="{url page="search"}" target="_parent">{translate key="navigation.search"}</a></li>
		{/if}

		{if $currentJournal && $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
			<li><a href="{url page="issue" op="current"}" target="_parent">{translate key="navigation.current"}</a></li>
			<li><a href="{url page="issue" op="archive"}" target="_parent">{translate key="navigation.archives"}</a></li>
		{/if}

		{if $enableAnnouncements}
			<li><a href="{url page="announcement"}" target="_parent">{translate key="announcement.announcements"}</a></li>
		{/if}{* $enableAnnouncements *}

		{call_hook name="Templates::Common::Header::Navbar::CurrentJournal"}

		{foreach from=$navMenuItems item=navItem}
			{if $navItem.url != '' && $navItem.name != ''}
				<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{url page=$requestedPage}{$navItem.url|escape}{/if}" target="_parent">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>
<div id="breadcrumb">
	<a href="{url page="index"}" target="_parent">{translate key="navigation.home"}</a> &gt;
	{if $issue}<a href="{url page="issue" op="view" path=$issue->getBestIssueId($currentJournal)}" target="_parent">{$issue->getIssueIdentification(false,true)|escape}</a> &gt;{/if}
	<a href="{url page="article" op="view" path=$articleId|to_array:$galleyId}" class="current" target="_parent">{$article->getFirstAuthor(true)|escape}</a>
</div>

<div id="content">
