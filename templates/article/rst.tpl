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
		{if $journalRt->getPrinterFriendly()}<li><a href="">{translate key="rst.printVersion"}</a></li>{/if}
		{if $journalRt->getDefineTerms()}<li><a href="">{translate key="rst.lookUp"}</a></li>{/if}
		{if $journalRt->getEmailOthers()}<li><a href="">{translate key="rst.colleague"}</a></li>{/if}
		{if $journalRt->getAddComment()}<li><a href="">{translate key="rst.addComment"}</a></li>{/if}
		{if $journalRt->getEmailAuthor()}<li><a href="">{translate key="rst.emailAuthor"}</a></li>{/if}
	</ul>
</div>
<br />
{/if}


<div class="rstBlock">
	<span>{translate key="rst.relatedItems"}</span>
	<ul>
		<li><a href="">{translate key="rst.researchStudies"}</a></li>
		<li><a href="">{translate key="rst.authorWorks"}</a></li>
		<li><a href="">{translate key="rst.dissertations"}</a></li>
		<li><a href="">{translate key="rst.pressAndMedia"}</a></li>
		<li><a href="">{translate key="rst.governmentWebsites"}</a></li>
		<li><a href="">{translate key="rst.instructionalResources"}</a></li>
		<li><a href="">{translate key="rst.discussionsAndForums"}</a></li>
		<li><a href="">{translate key="rst.google"}</a></li>
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
