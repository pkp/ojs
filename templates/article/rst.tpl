{**
 * rst.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools.
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

<div id="main" style="width: 160px;">

<h5>{$issue->getIssueIdentification()}</h5>

<div id="navbar">
	<ul class="menu">
		<li><a href="{$pageUrl}/issue/view/{$issue->getIssueId()}" target="_parent">{translate key="issue.toc"}</a></li>
	</ul>
</div>

<h6>{$article->getTitle()|truncate:45}<br /><i>{$article->getFirstAuthor()}</i></h6>
<div class="rstSeparator"></div>

<h6>{translate key="rst.readingTools"}</h6>

{if $journalRt}
<div class="rstBlock">
	<span>{translate key="rst.peerReviewed"}</span>
	<ul>
		{if $journalRt->getAuthorBio()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/bio/{$articleId}/{$galleyId}');">{translate key="rst.authorBio"}</a></li>{/if}
		{if $journalRt->getCaptureCite()}<li><a href="">{translate key="rst.captureCitation"}</a></li>{/if}
		{if $journalRt->getViewMetadata()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/metadata/{$articleId}/{$galleyId}');">{translate key="rst.viewMetadata"}</a></li>{/if}
		{if $journalRt->getSupplementaryFiles()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/suppFiles/{$articleId}/{$galleyId}');">{translate key="rst.suppFiles"}</a></li>{/if}
		{if $journalRt->getPrinterFriendly()}<li><a href="javascript:openRTWindow('{$pageUrl}/rt/printerFriendly/{$articleId}/{$galleyId}');">{translate key="rst.printVersion"}</a></li>{/if}
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
					<a href="javascript:openWindow('{$pageUrl}/rt/emailColleague/{$articleId}/{$galleyId}');">{translate key="rst.colleague"}</a>
				{else}
					{translate key="rst.colleague"}&nbsp;*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{if $journalRt->getAddComment()}<li><a href="javascript:openWindow('{$pageUrl}/rt/addComment/{$articleId}/{$galleyId}');">{translate key="rst.addComment"}</a></li>{/if}
		{if $journalRt->getEmailAuthor()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openWindow('{$pageUrl}/rt/emailAuthor/{$articleId}/{$galleyId}');">{translate key="rst.emailAuthor"}</a>
				{else}
					{translate key="rst.emailAuthor"}&nbsp;*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
	</ul>
	{if $needsLoginNote}
		<i style="font-size: 0.9em">{translate key="rst.email.needLogin" pageUrl=$pageUrl}</i>
	{/if}
</div>
<br />
{/if}


<div class="rstBlock">
	<span>{translate key="rst.relatedItems"}</span>
	<ul>
		{foreach from=$version->getContexts() item=context}
			{if !$context->getDefineTerms()}
				<li><a href="javascript:openRTWindow('{$pageUrl}/rt/context/{$articleId}/{$galleyId}/{$context->getContextId()}');">{$context->getTitle()}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

<div class="rstSeparatorThin"></div>

<div class="rstBlock">
	<ul>
		<li><a href="{$requestPageUrl}/viewArticle/{$articleId}/{$galleyId}" target="_parent">{translate key="common.close"}</a></li>
	</ul>
</div>

</div>

</div>

</body>

</html>
