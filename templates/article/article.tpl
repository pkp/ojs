{**
 * article.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$article->getArticleTitle()|escape} | {$article->getFirstAuthor(true)|escape} | {$currentJournal->getJournalTitle()|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="description" content="{$article->getArticleTitle()|escape}" />
	{if $article->getArticleSubject()}
	<meta name="keywords" content="{$article->getArticleSubject()|escape}" />
	{/if}

	{include file="article/dublincore.tpl"}
	{include file="article/googlescholar.tpl"}

	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
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
		<li><a href="{url page="user"}" target="_parent">{translate key="navigation.userHome"}</a></li>
		{else}
		<li><a href="{url page="login"}" target="_parent">{translate key="navigation.login"}</a></li>
		<li><a href="{url page="user" op="register"}" target="_parent">{translate key="navigation.register"}</a></li>
		{/if}
		<li><a href="{url page="search"}" target="_parent">{translate key="navigation.search"}</a></li>
		{if $currentJournal}
		<li><a href="{url page="issue" op="current"}" target="_parent">{translate key="navigation.current"}</a></li>
		<li><a href="{url page="issue" op="archive"}" target="_parent">{translate key="navigation.archives"}</a></li>
		{/if}
		{foreach from=$navMenuItems item=navItem}
		{if $navItem.url != '' && $navItem.name != ''}<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{url page=$requestedPage}{$navItem.url|escape}{/if}" target="_parent">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>{/if}
		{/foreach}
	</ul>
</div>
<div id="breadcrumb">
	<a href="{url page="index"}" target="_parent">{translate key="navigation.home"}</a> &gt;
	{if $issue}<a href="{url page="issue" op="view" path=$issue->getBestIssueId($currentJournal)}" target="_parent">{$issue->getIssueIdentification(false,true)|escape}</a> &gt;{/if}
	<a href="{url page="article" op="view" path=$articleId|to_array:$galleyId}" class="current" target="_parent">{$article->getFirstAuthor(true)|escape}</a>
</div>

<div id="content">
{if $galley}
	{if $galley->isHTMLGalley()}
		{$galley->getHTMLContents()}
	{/if}
{else}
	{assign var=galleys value=$article->getLocalizedGalleys()}
	{if $galleys && $subscriptionRequired && $showGalleyLinks}
		<div id="accessKey">
			<img src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
			{translate key="reader.openAccess"}&nbsp;
			<img src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
			{if $purchaseArticleEnabled}
				{translate key="reader.subscriptionOrFeeAccess"}
			{else}
				{translate key="reader.subscriptionAccess"}
			{/if}
		</div>
	{/if}
	<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>
	<div><em>{$article->getAuthorString()|escape}</em></div>
	<br />
	{if !$section->getAbstractsDisabled()}
		<h4>{translate key="article.abstract"}</h4>
		<br />
		<div>{$article->getArticleAbstract()|strip_unsafe_html|nl2br}</div>
		<br />
	{/if}

	{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser || $subscribedDomain)}
		{assign var=hasAccess value=1}
	{else}
		{assign var=hasAccess value=0}
	{/if}
	
	{if $galleys}
		{translate key="reader.fullText"}
		{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
			{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
				<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal)}" class="file" target="_parent">{$galley->getGalleyLabel()|escape}</a>
				{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
					{if $article->getAccessStatus() || !$galley->isPdfGalley()}	
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
			{/foreach}
			{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
				{if $article->getAccessStatus()}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
				{else}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
				{/if}
			{/if}					
		{else}
			&nbsp;<a href="{url page="about" op="subscriptions"}" target="_parent">{translate key="reader.subscribersOnly"}</a>
		{/if}
	{/if}
{/if}

{if $comments}
<div class="separator"></div>
<h4>{translate key="comments.commentsOnArticle"}</h4>

<ul>
{foreach from=$comments item=comment}
{assign var=poster value=$comment->getUser()}
	<li>
		<a href="{url page="comment" op="view" path=$article->getArticleId()|to_array:$galleyId:$comment->getCommentId()}" target="_parent">{$comment->getTitle()|escape|default:"&nbsp;"}</a>
		{if $comment->getChildCommentCount()==1}
			{translate key="comments.oneReply"}
		{elseif $comment->getChildCommentCount()>0}
			{translate key="comments.nReplies" num=$comment->getChildCommentCount()}
		{/if}

		<br/>

		{if $poster}
			{url|assign:"publicProfileUrl" page="user" op="viewPublicProfile" path=$poster->getUserId()}
			{translate key="comments.authenticated" userName=$poster->getFullName()|escape publicProfileUrl=$publicProfileUrl}
		{elseif $comment->getPosterName()}
			{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}
		{else}
			{translate key="comments.anonymous"}
		{/if}
		({$comment->getDatePosted()|date_format:$dateFormatShort})
	</li>
{/foreach}
</ul>

<a href="{url page="comment" op="view" path=$article->getArticleId()|to_array:$galleyId}" class="action" target="_parent">{translate key="comments.viewAllComments"}</a>

{assign var=needsSeparator value=1}

{/if}{* $comments *}

{if $postingAllowed}
	{if $needsSeparator}
		&nbsp;|&nbsp;
	{else}
		<br/><br/>
	{/if}
	<a class="action" href="{url page="comment" op="add" path=$article->getArticleId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a>
{/if}

{if $currentJournal && $currentJournal->getSetting('includeCreativeCommons')}
	<br /><br />
	<a rel="license" target="_new" href="http://creativecommons.org/licenses/by/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by/3.0/80x15.png" /></a>
	<br />
	This <span xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://purl.org/dc/dcmitype/Text" rel="dc:type">work</span> is licensed under a <a target="_new" rel="license" href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 License</a>.
{/if}

{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
{call_hook name="Templates::Article::Footer::PageFooter"}
</div>

</div>
</div>
</div>

{if $defineTermsContextId}
<script type="text/javascript">
{literal}
<!--
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
		if (url.indexOf('?') > -1) openRTWindowWithToolbar(url + '&defineTerm=' + term);
		else openRTWindowWithToolbar(url + '?defineTerm=' + term);
	}

	if(document.captureEvents) {
		document.captureEvents(Event.DBLCLICK);
	}
	document.ondblclick = new Function("openSearchTermWindow('{/literal}{url page="rt" op="context" path=$articleId|to_array:$galleyId:$defineTermsContextId escape=false}{literal}')");
// -->
{/literal}
</script>
{/if}

{get_debug_info}
{if $enableDebugStats}
<div id="footer">
	<div id="footerContent">
		<div class="debugStats">
		{translate key="debug.executionTime"}: {$debugExecutionTime|string_format:"%.4f"}s<br />
		{translate key="debug.databaseQueries"}: {$debugNumDatabaseQueries|escape}<br/>
		{if $debugNotes}
			<strong>{translate key="debug.notes"}</strong><br/>
			{foreach from=$debugNotes item=note}
				{translate key=$note[0] params=$note[1]}<br/>
			{/foreach}
		{/if}
		</div>
	</div><!-- footerContent -->
</div><!-- footer -->
{/if}

</body>
</html>
