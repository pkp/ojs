{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show list of an author's submissions.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submissions"}
{assign var="pageId" value="author.submissions"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td width="60%">{translate key="article.title"}</td>
	<td><nobr>{translate key="common.dateSubmitted"}</nobr></td>
	<td>{translate key="article.section"}</td>
	<td colspan="2">{translate key="common.status"}</td>
</tr>
{foreach from=$submissions item=article}
<tr class="{cycle values="row,rowAlt"}">
	<td>
	{if $article->getSubmissionProgress()}
		{$article->getArticleID()}
	{else}
		{if $article->getDecisions()|@count gt 0}
			{assign var="toEdit" value="false"}
			{assign var="round" value=$article->getCurrentRound()}
			{foreach from=$article->getDecisions($round) item=editorDecision}
				{if $editorDecision.decision eq $acceptEditorDecisionValue}
					{assign var="toEdit" value="true"}
				{else}
					{assign var="toEdit" value="false"}
				{/if}
			{/foreach}
			{if $toEdit eq "true"}
				<a href="{$pageUrl}/author/submissionEditing/{$article->getArticleId()}">{$article->getArticleID()}</a>
			{else}
				<a href="{$pageUrl}/author/submission/{$article->getArticleId()}">{$article->getArticleID()}</a>
			{/if}
		{/if}
	{/if}
	</td>
	<td>{$article->getArticleTitle()}</td>
	<td>{if $article->getDateSubmitted()}{$article->getDateSubmitted()|date_format:$dateFormatShort}{else}-{/if}</td>
	<td>{$article->getSectionTitle()}</td>
	{if $article->getSubmissionProgress()}
	<td><a href="{$pageUrl}/author/submit/{$article->getSubmissionProgress()}?articleId={$article->getArticleId()}" class="tableAction">{translate key="author.submissions.incomplete"}</a>
	</td>
	<td><a href="{$pageUrl}/author/deleteSubmission/{$article->getArticleId()}" onclick="return confirm('{translate|escape:"javascript" key="author.submissions.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></td>
	{else}
	<td colspan="2">
		{if $toEdit eq "true"}
			<a href="{$pageUrl}/author/submissionEditing/{$article->getArticleId()}" class="tableAction">{translate key="author.submissions.viewStatus"}</a>
		{else}
			<a href="{$pageUrl}/author/submission/{$article->getArticleId()}" class="tableAction">{translate key="author.submissions.viewStatus"}</a>
		{/if}
	{/if}
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="author.submissions.noSubmissions"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
