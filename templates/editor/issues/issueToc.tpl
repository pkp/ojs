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
{assign var="pageTitleTranslated" value=$issue->getIssueIdentification()}
{assign var="pageCrumbTitleTranslated" value=$issue->getIssueIdentification(false,true)}
{else}
{assign var="pageTitle" value="editor.issues.noLiveIssues"}
{assign var="pageCrumbTitle" value="editor.issues.noLiveIssues"}
{/if}
{assign var="pageId" value="editor.issues.issueToc"}
{include file="common/header.tpl"}

{if not $noIssue}

<ul class="menu">
	<li class="current"><a href="{$requestPageUrl}/issueToc/{$issueId}">{translate key="issue.toc"}</a></li>
	<li><a href="{$requestPageUrl}/issueData/{$issueId}">{translate key="editor.issues.issueData"}</a></li>
</ul>

<br />

<form>
{translate key="editor.issues.liveIssues"}&nbsp;&nbsp;<select name="issue" class="selectMenu" onchange="location.href='{$requestPageUrl}/issueToc/'+this.options[this.selectedIndex].value" size="1">{html_options options=$issueOptions selected=$issueId}</select>
</form>

<table width="100%" class="data">
	{if $issue->getTitle()}
	<tr>
		<td width="15%" class="label">{translate key="common.title"}</td>
		<td>{$issue->getTitle()}</td>
	</tr>
	{/if}
	{if $issue->getDescription()}
	<tr>
		<td width="15%" class="label" valign="top">{translate key="common.description"}</td>
		<td>{$issue->getDescription()|nl2br}</td>
	</tr>
	{/if}
</table>


<form id="issueToc" method="post" action="{$pageUrl}/editor/updateIssueToc/{$issueId}" onsubmit="return confirm('{translate|escape:"javascript" key="editor.issues.saveChanges"}')">

{foreach from=$sections item=section}

<table>
	<tr>
		<td><h4>{$section[1]}&nbsp;<a href="{$pageUrl}/editor/moveSectionToc/{$issueId}?d=u&amp;sectionId={$section[0]}">&uarr;</a>&nbsp;<a href="{$pageUrl}/editor/moveSectionToc/{$issueId}?d=d&amp;sectionId={$section[0]}">&darr;</a></h4></td>
	</tr>
</table>

<table width="100%" class="listing">
	<tr>
		<td colspan="7" class="headseparator"></td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="8%">{translate key="editor.issues.order"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td width="{$titleWidth}%">{translate key="article.title"}</td>
		{if (($issueAccess == 2) && $enableSubscriptions)}<td width="12%">{translate key="editor.issues.access"}</td>{/if}
		{if $enablePublicArticleId}<td width="12%">{translate key="editor.issues.publicId"}</td>{/if}
		{if $enablePageNumber}<td width="12%">{translate key="editor.issues.pages"}</td>{/if}
		<td width="7%">{translate key="common.remove"}</td>
	</tr>
	<tr>
		<td colspan="7" class="headseparator"></td>
	</tr>

	{foreach from=$section[2] item=article name="currSection"}

	{assign var="articleId" value=$article->getArticleID()}
	<tr>
		<td>{$article->getSeq()}&nbsp;<a href="{$pageUrl}/editor/moveArticleToc/{$issueId}?d=u&amp;sectionId={$section[0]}&amp;pubId={$article->getPubId()}">&uarr;</a>&nbsp;<a href="{$pageUrl}/editor/moveArticleToc/{$issueId}?d=d&amp;sectionId={$section[0]}&amp;pubId={$article->getPubId()}">&darr;</a></td>
		<td>
			<div>
			{foreach from=$article->getAuthors() item=author name=authorList}
				{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
			{/foreach}
			</div>				
		</td>
		<td><a href="{$requestPageUrl}/submission/{$articleId}">{$article->getArticleTitle()|truncate:$truncateSize:"..."}</a></td>
		{if (($issueAccess == 2) && $enableSubscriptions)}
		<td><select name="accessStatus[{$article->getPubId()}]" size="1" class="selectMenu">{html_options options=$accessOptions selected=$article->getAccessStatus()}</select></td>
		{/if}
		{if $enablePublicArticleId}
		<td><input type="text" name="publishedArticles[{$article->getArticleId()}]" value="{$article->getPublicArticleId()|escape}" size="10" maxlength="10" class="textField" /></td>
		{/if}
		{if $enablePageNumber}<td width="12%"><input type="text" name="pages[{$article->getArticleId()}]" value="{$article->getPages()|escape}" size="10" maxlength="10" class="textField" /></td>{/if}
		<td><input type="checkbox" name="remove[{$article->getArticleId()}]" value="{$article->getPubId()}" /></td>
	</tr>
	<tr>
		<td colspan="7" class="{if $smarty.foreach.currSection.last}end{/if}separator"></td>
	</tr>

	{/foreach}

</table>

{/foreach}

<p><input type="submit" value="{translate key="common.saveChanges"}" class="button defaultButton" /> {if $unpublished}<input type="button" value="{translate key="editor.issues.publishIssue"}" onclick="confirmAction('{$requestPageUrl}/publishIssue/{$issueId}', '{translate|escape:"javascript" key="editor.issues.confirmPublish"}')" class="button" />{/if}</p>

</form>

{/if}

{include file="common/footer.tpl"}
