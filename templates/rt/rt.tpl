{**
 * rt.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reading Tools.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$article->getFirstAuthor(true)|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/rt.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>

<div id="container">
<div id="main" style="width: 150px; font-size: 0.7em; padding-top: 1.5em; padding-left: 1em">

<h5>{$journal->getJournalInitials()|escape}{if $issue}<br />{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}{/if}</h5>

{if $issue}<p><a href="{url page="issue" op="view" path=$issue->getBestIssueId($journal)|to_array:"showToc"}" target="_parent" class="rtAction">{translate key="issue.toc"}</a></p>{/if}

<h5>{translate key="rt.readingTools"}</h5>

<div class="rtSeparator"></div>

<h6>{$article->getArticleTitle()|strip_unsafe_html|truncate:20:"...":true}</h6>
<p><em>{$article->getAuthorString(true)|escape}</em></p>

<div class="rtSeparator"></div>

<br />

{if $journalRt->getEnabled()}
<div class="rtBlock">
	<ul>
		{if $journalRt->getAbstract() && $galley && $article->getArticleAbstract()}<li><a href="{url page="article" op="view" path=$articleId}" target="_parent">{translate key="article.abstract"}</a></li>{/if}
		<li><a href="{url page="about" op="editorialPolicies" anchor="peerReviewProcess"}" target="_parent">{translate key="rt.reviewPolicy"}</a></li>
		{if $journalRt->getAuthorBio()}<li><a href="javascript:openRTWindow('{url page="rt" op="bio" path=$articleId|to_array:$galleyId}');">{translate key="rt.authorBio"}</a></li>{/if}
		{if $journalRt->getCaptureCite()}<li><a href="javascript:openRTWindow('{url page="rt" op="captureCite" path=$articleId|to_array:$galleyId}');">{translate key="rt.captureCite"}</a></li>{/if}
		{if $journalRt->getViewMetadata()}<li><a href="javascript:openRTWindow('{url page="rt" op="metadata" path=$articleId|to_array:$galleyId}');">{translate key="rt.viewMetadata"}</a></li>{/if}
		{if $journalRt->getSupplementaryFiles() && $article->getSuppFiles()}<li><a href="javascript:openRTWindow('{url page="rt" op="suppFiles" path=$articleId|to_array:$galleyId}');">{translate key="rt.suppFiles"}</a></li>{/if}
		{if $journalRt->getPrinterFriendly()}<li><a href="{if !$galley || $galley->isHtmlGalley()}javascript:openRTWindow('{url page="rt" op="printerFriendly" path=$articleId|to_array:$galleyId}');{else}{url page="article" op="download" path=$articleId|to_array:$galley->getGalleyId()}{/if}">{translate key="rt.printVersion"}</a></li>{/if}
		{if $journalRt->getDefineTerms() && $version}
			{foreach from=$version->getContexts() item=context}
				{if $context->getDefineTerms()}
					<li><a href="javascript:openRTWindowWithToolbar('{url page="rt" op="context" path=$articleId|to_array:$galleyId:$context->getContextId()}');">{$context->getTitle()|escape}</a></li>
				{/if}
			{/foreach}
		{/if}
		{if $journalRt->getEmailOthers()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{url page="rt" op="emailColleague" path=$articleId|to_array:$galleyId}');">{translate key="rt.colleague"}</a>
				{else}
					{translate key="rt.colleague"}*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{if $journalRt->getEmailAuthor()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{url page="rt" op="emailAuthor" path=$articleId|to_array:$galleyId}');">{translate key="rt.emailAuthor"}</a>
				{else}
					{translate key="rt.emailAuthor"}*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{if $postingAllowed && $postingDisabled}
			<li>
			{translate key="rt.addComment"}*
			{assign var=needsLoginNote value=1}
			</li>
		{elseif $postingAllowed}
			<li><a href="{url page="comment" op="add" path=$article->getArticleId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a></li>
		{/if}
		{if $journalRt->getFindingReferences()}
			<li><a href="javascript:openRTWindow('{url page="rt" op="findingReferences" path=$article->getArticleId()|to_array:$galleyId}');">{translate key="rt.findingReferences"}</a></li>
		{/if}
	</ul>
</div>
<br />
{/if}

{if $version}
<div class="rtBlock">
	<span class="rtSubtitle">{translate key="rt.relatedItems"}</span>
	<ul>
		{foreach from=$version->getContexts() item=context}
			{if !$context->getDefineTerms()}
				<li><a href="javascript:openRTWindowWithToolbar('{url page="rt" op="context" path=$articleId|to_array:$galleyId:$context->getContextId()}');">{$context->getTitle()|escape}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>
{/if}

<br />

<div class="rtBlock">
	<span class="rtSubtitle">{translate key="rt.thisJournal"}</span>
	<form method="post" action="{url page="search" op="results"}" target="_parent">
	<table>
	<tr>
		<td><input type="text" id="query" name="query" size="15" maxlength="255" value="" class="textField" /></td>
	</tr>
	<tr>
		<td><select name="searchField" size="1" class="selectMenu">
			{html_options_translate options=$articleSearchByOptions}
		</select></td>
	</tr>
	<tr>
		<td><input type="submit" value="{translate key="common.search"}" class="button" /></td>
	</tr>
	</table>
	</form>
</div>

{if $currentJournal && $currentJournal->getSetting('includeCreativeCommons')}
	{translate key="common.ccLicense.rt"}
{/if}

<div class="rtSeparatorThin"></div>

{if $galley}
	{if $galley->isHtmlGalley()}
		<a href="{url op="viewArticle" path=$articleId|to_array:$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
	{elseif $galley->isPdfGalley()}
		<a href="{url op="viewPDFInterstitial" path=$articleId|to_array:$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
	{else}
		<a href="{url op="viewDownloadInterstitial" path=$articleId|to_array:$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
	{/if}
{else}
	<a href="{url op="viewArticle" path=$articleId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
{/if}

{if $needsLoginNote}
{url|assign:"loginUrl" page="user" op="register"}
<p><em style="font-size: 0.9em">{translate key="rt.email.needLogin" loginUrl=$loginUrl}</em></p>
{/if}

</div>

</div>

</body>

</html>
