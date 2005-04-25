{**
 * rt.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reading Tools.
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
<div id="main" style="width: 160px; font-size: 0.7em; padding-top: 1.5em">

<h5>{$journal->getSetting('journalInitials')}<br />{$issue->getIssueIdentification()}</h5>

<p><a href="{$pageUrl}/issue/view/{$issue->getIssueId()}" target="_parent" class="rtAction">{translate key="issue.toc"}</a></p>

<div class="rtSeparator"></div>

<h6>{$article->getTitle()|truncate:20}</h6>
<p><i>{$article->getAuthorString(true)}</i></p>

<div class="rtSeparator"></div>

<br />

<h5>{translate key="rt.readingTools"}</h5>

{if $journalRt && $journalRt->getVersion()}
<div class="rtBlock">
	<span class="rtSubtitle">{translate key="rt.peerReviewed"}</span>
	<ul>
		{if $journalRt->getAuthorBio()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/bio/{$articleId}/{$galleyId}');">{translate key="rt.authorBio"}</a></li>{/if}
		{if $journalRt->getCaptureCite()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/captureCite/{$articleId}/{$galleyId}');">{translate key="rt.captureCitation"}</a></li>{/if}
		{if $journalRt->getViewMetadata()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/metadata/{$articleId}/{$galleyId}');">{translate key="rt.viewMetadata"}</a></li>{/if}
		{if $journalRt->getSupplementaryFiles() && $article->getSuppFiles()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/suppFiles/{$articleId}/{$galleyId}');">{translate key="rt.suppFiles"}</a></li>{/if}
		{if $journalRt->getPrinterFriendly()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/printerFriendly/{$articleId}/{$galleyId}');">{translate key="rt.printVersion"}</a></li>{/if}
		{if $journalRt->getDefineTerms()}
			{foreach from=$version->getContexts() item=context}
				{if $context->getDefineTerms()}
					<li><a href="javascript:openRTWindow('{$pageUrl}/rt/context/{$articleId}/{$galleyId}/{$context->getContextId()}');">{$context->getTitle()}</a></li>
				{/if}
			{/foreach}
		{/if}
		{if $journalRt->getEmailOthers()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{$pageUrl}/rt/emailColleague/{$articleId}/{$galleyId}');">{translate key="rt.colleague"}</a>
				{else}
					{translate key="rt.colleague"}&nbsp;*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{if $journalRt->getAddComment() && ($enableComments=='unauthenticated' || (($enableComments=='authenticated' || $enableComments=='anonymous') && $isUserLoggedIn))}
			<li><a href="{$pageUrl}/comment/add/{$articleId}/{$galleyId}" target="_parent">{translate key="rt.addComment"}</a></li>
		{elseif $enableComments=='authenticated' || $enableComments=='anonymous'}
			{translate key="rt.addComment"}&nbsp;*
			{assign var=needsLoginNote value=1}
		{/if}
		{if $journalRt->getEmailAuthor()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{$pageUrl}/rt/emailAuthor/{$articleId}/{$galleyId}');">{translate key="rt.emailAuthor"}</a>
				{else}
					{translate key="rt.emailAuthor"}&nbsp;*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
	</ul>
</div>
<br />
{/if}


<div class="rtBlock">
	<span class="rtSubtitle">{translate key="rt.relatedItems"}</span>
	<ul>
		{foreach from=$version->getContexts() item=context}
			{if !$context->getDefineTerms()}
				<li><a href="javascript:openRTWindow('{$pageUrl}/rt/context/{$articleId}/{$galleyId}/{$context->getContextId()}');">{$context->getTitle()}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

<div class="rtSeparatorThin"></div>

<a href="{$requestPageUrl}/viewArticle/{$articleId}/{$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>

{if $needsLoginNote}
<p><i style="font-size: 0.9em">{translate key="rt.email.needLogin" pageUrl=$pageUrl}</i></p>
{/if}

</div>

</div>

</body>

</html>
