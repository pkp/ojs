{**
 * issueToc.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's table of contents
 *
 * $Id$
 *}

{if not $noIssue}
{assign var="pageTitleTranslated" value=$issue->getIssueIdentification()|escape}
{assign var="pageCrumbTitleTranslated" value=$issue->getIssueIdentification(false,true)|escape}
{else}
{assign var="pageTitle" value="editor.issues.noLiveIssues"}
{assign var="pageCrumbTitle" value="editor.issues.noLiveIssues"}
{/if}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
	<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="common.queue.short.submissionsInScheduling"}</a></li>
	<li{if $unpublished} class="current"{/if}><a href="{$pageUrl}/editor/futureIssues">{translate key="editor.navigation.futureIssues"}</a></li>
	<li{if !$unpublished} class="current"{/if}><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

{if not $noIssue}
<br />

<form action="#">
{translate key="issue.issue"}: <select name="issue" class="selectMenu" onchange="if(this.options[this.selectedIndex].value > 0) location.href='{$requestPageUrl}/issueToc/'+this.options[this.selectedIndex].value" size="1">{html_options options=$issueOptions selected=$issueId}</select>
</form>

<div class="separator"></div>

<ul class="menu">
	<li class="current"><a href="{$requestPageUrl}/issueToc/{$issueId}">{translate key="issue.toc"}</a></li>
	<li><a href="{$requestPageUrl}/issueData/{$issueId}">{translate key="editor.issues.issueData"}</a></li>
	{if $unpublished}<li><a href="{$pageUrl}/issue/view/{$issue->getBestIssueId()}">{translate key="editor.issues.previewIssue"}</a></li>{/if}
</ul>

<h3>{translate key="issue.toc"}</h3>

<form method="post" action="{$pageUrl}/editor/updateIssueToc/{$issueId}" onsubmit="return confirm('{translate|escape:"javascript" key="editor.issues.saveChanges"}')">

{foreach from=$sections item=section}
<h4>{$section[1]}{* #1635# <a href="{$pageUrl}/editor/moveSectionToc/{$issueId}?d=u&amp;sectionId={$section[0]}" class="plain">&uarr;</a> <a href="{$pageUrl}/editor/moveSectionToc/{$issueId}?d=d&amp;sectionId={$section[0]}" class="plain">&darr;</a>*}</h4>

<table width="100%" class="listing">
	<tr>
		<td colspan="8" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="10%" colspan="2">{translate key="editor.issues.order"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td>{translate key="article.title"}</td>
		{if (($issueAccess == 2) && $enableSubscriptions)}<td width="10%">{translate key="editor.issues.access"}</td>{/if}
		{if $enablePublicArticleId}<td width="10%">{translate key="editor.issues.publicId"}</td>{/if}
		{if $enablePageNumber}<td width="10%">{translate key="editor.issues.pages"}</td>{/if}
		<td width="5%">{translate key="common.remove"}</td>
	</tr>
	<tr>
		<td colspan="8" class="headseparator">&nbsp;</td>
	</tr>

	{foreach from=$section[2] item=article name="currSection"}

	{assign var="articleId" value=$article->getArticleID()}
	<tr>
		<td>{$article->getSeq()}.</td>
		<td><a href="{$pageUrl}/editor/moveArticleToc/{$issueId}?d=u&amp;sectionId={$section[0]}&amp;pubId={$article->getPubId()}" class="plain">&uarr;</a>&nbsp;<a href="{$pageUrl}/editor/moveArticleToc/{$issueId}?d=d&amp;sectionId={$section[0]}&amp;pubId={$article->getPubId()}" class="plain">&darr;</a></td>
		<td>
			{foreach from=$article->getAuthors() item=author name=authorList}
				{$author->getLastName()|escape}{if !$smarty.foreach.authorList.last},{/if}
			{/foreach}
		</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$article->getArticleTitle()|truncate:60:"..."|escape}</a></td>
		{if (($issueAccess == 2) && $enableSubscriptions)}
		<td><select name="accessStatus[{$article->getPubId()}]" size="1" class="selectMenu">{html_options options=$accessOptions selected=$article->getAccessStatus()}</select></td>
		{/if}
		{if $enablePublicArticleId}
		<td><input type="text" name="publishedArticles[{$article->getArticleId()}]" value="{$article->getPublicArticleId()|escape}" size="10" maxlength="255" class="textField" /></td>
		{/if}
		{if $enablePageNumber}<td width="12%"><input type="text" name="pages[{$article->getArticleId()}]" value="{$article->getPages()|escape}" size="10" maxlength="255" class="textField" /></td>{/if}
		<td><input type="checkbox" name="remove[{$article->getArticleId()}]" value="{$article->getPubId()}" /></td>
	</tr>
	<tr>
		<td colspan="8" class="{if $smarty.foreach.currSection.last}end{/if}separator">&nbsp;</td>
	</tr>

	{/foreach}
</table>
{foreachelse}
<p><em>{translate key="editor.issues.noArticles"}</em></p>

<div class="separator"></div>
{/foreach}

<input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if $unpublished}<input type="button" value="{translate key="editor.issues.publishIssue"}" onclick="confirmAction('{$requestPageUrl}/publishIssue/{$issueId}', '{translate|escape:"javascript" key="editor.issues.confirmPublish"}')" class="button" />{/if}

</form>

{/if}

{include file="common/footer.tpl"}
