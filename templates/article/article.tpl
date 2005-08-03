{**
 * article.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{$article->getFirstAuthor(true)|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />
	{foreach from=$stylesheets item=cssFile}
	<link rel="stylesheet" href="{$baseUrl}/styles/{$cssFile}" type="text/css" />
	{/foreach}
	{if $pageStyleSheet}
	<link rel="stylesheet" href="{$publicFilesDir}/{$pageStyleSheet.uploadName}" type="text/css" />
	{/if}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>

<div id="container">

<div id="body">

<div id="main">

<h2>{$siteTitle|escape},&nbsp;{$issue->getIssueIdentification(false,true)|escape}</h2>

<div id="navbar">
	<ul class="menu">
		<li><a href="{$pageUrl}" target="_parent">{translate key="navigation.home"}</a></li>
		<li><a href="{$pageUrl}/about" target="_parent">{translate key="navigation.about"}</a></li>
		{if $isUserLoggedIn}
		<li><a href="{$pageUrl}/user" target="_parent">{translate key="navigation.userHome"}</a></li>
		{else}
		<li><a href="{$pageUrl}/login" target="_parent">{translate key="navigation.login"}</a></li>
		<li><a href="{$pageUrl}/user/register" target="_parent">{translate key="navigation.register"}</a></li>
		{/if}
		<li><a href="{$pageUrl}/search" target="_parent">{translate key="navigation.search"}</a></li>
		{if $currentJournal}
		<li><a href="{$pageUrl}/issue/current" target="_parent">{translate key="navigation.current"}</a></li>
		<li><a href="{$pageUrl}/issue/archive" target="_parent">{translate key="navigation.archives"}</a></li>
		{/if}
		{foreach from=$navMenuItems item=navItem}
		<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$pageUrl}{$navItem.url|escape}{/if}" target="_parent">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{$pageUrl}" target="_parent">{translate key="navigation.home"}</a> &gt;
	<a href="{$pageUrl}/issue/view/{$issue->getBestIssueId($currentJournal)|escape:"url"}" target="_parent">{$issue->getIssueIdentification(false,true)|escape}</a> &gt;
	<a href="{$pageUrl}/article/view/{$articleId|escape:"url"}/{$galleyId|escape:"url"}" class="current" target="_parent">{$article->getFirstAuthor(true)|escape}</a>
</div>

<div id="content">
{if $galley}
	{$galley->getHTMLContents("$requestPageUrl/viewFile")}
{else}

	<h3>{$article->getArticleTitle()|escape}</h3>
	<div><i>{$article->getAuthorString()|escape}</i></div>
	<br />
	<h4>{translate key="issue.abstract"}</h4>
	<br />
	<div>{$article->getArticleAbstract()|strip_unsafe_html|nl2br}</div>
	<br />

	{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser || $subscribedDomain)}
		{assign var=galleys value=$article->getGalleys()}
		{if $galleys}
			{translate key="reader.fullText"}
			{foreach from=$galleys item=galley name=galleyList}
				<a href="{$pageUrl}/article/view/{$articleId}/{$galley->getGalleyId()}" class="action" target="_parent">{$galley->getLabel()|escape}</a>
			{/foreach}
		{/if}
	{else}
		{translate key="reader.fullTextSubscribersOnly"}
	{/if}
{/if}

{if $comments}
<div class="separator"></div>
<h4>{translate key="comments.commentsOnArticle"}</h4>

<ul>
{foreach from=$comments item=comment}
{assign var=poster value=$comment->getUser()}
	<li>
		<a href="{$pageUrl}/comment/view/{$article->getArticleId()}/{$galleyId|escape:"url"}/{$comment->getCommentId()}" target="_parent">{$comment->getTitle()|escape}</a>
		{if $comment->getChildCommentCount()==1}{translate key="comments.oneReply"}{elseif $comment->getChildCommentCount()>0}{translate key="comments.nReplies" num=$comment->getChildCommentCount()}{/if}<br/>
		{if $poster}{translate key="comments.authenticated" userName=$comment->getPosterName()|escape}{elseif $comment->getPosterName()}{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$comment->getDatePosted()|date_format:$dateFormatShort})
	</li>
{/foreach}
</ul>

<a href="{$pageUrl}/comment/view/{$article->getArticleId()}/{$galleyId|escape:"url"}" class="action" target="_parent">{translate key="comments.viewAllComments"}</a>{if $postingAllowed}&nbsp;|&nbsp;<a class="action" href="{$pageUrl}/comment/add/{$article->getArticleId()}/{$galleyId|escape:"url"}" target="_parent">{translate key="rt.addComment"}</a>{/if}<br />

{/if}

</div>

</div>
</div>
</div>

{if $defineTermsContextId}
<script type="text/javascript">
{literal}
	// Open "Define Terms" context when double-clicking any text
	function openSearchTermWindow(url) {
		var term;
		if (window.getSelection) {
			term = window.getSelection();
		} else if (document.getSelection) {
			term = document.getSelection();
		} else if(document.selection && document.selection.createRange && document.selection.type.toLowerCase() == 'text') {
			var range = document.selection.createRange();
			term = range.text;
		}
		openRTWindow(url + '?defineTerm=' + term);
	}

	if(document.captureEvents) {
		document.captureEvents(Event.DBLCLICK);
	}
	document.ondblclick = new Function("openSearchTermWindow('{/literal}{$pageUrl}/rt/context/{$articleId|escape:"url"}/{$galleyId|escape:"url"}/{$defineTermsContextId}{literal}')");
{/literal}
</script>
{/if}

</body>
</html>
