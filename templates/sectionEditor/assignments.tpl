{**
 * assignments.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Active editorial assignments.
 *
 * $Id$
 *}

{if $showCompleted}
{assign var="pageTitle" value="sectionEditor.completedEditorialAssignments"}
{else}
{assign var="pageTitle" value="sectionEditor.activeEditorialAssignments"}
{/if}
{assign var="pageId" value="sectionEditor.assignments"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="common.id"}</td>
	<td><a href="{$requestPageUrl}/activeAssignments?sort=section">{translate key="editor.article.section"}</a></td>
	<td>{translate key="editor.article.authors"}</td>
	<td><a href="{$requestPageUrl}/activeAssignments?sort=submitted">{translate key="editor.article.submitted"}</a></td>
	<td>{translate key="editor.article.editorReview"}</td>
	<td>{translate key="editor.article.copyedit"}</td>
	<td>{translate key="editor.article.layoutAndProof"}</td>
</tr>
{foreach from=$assignedArticles item=article}
<tr class="{cycle values="row,rowAlt"}">
	<td>
		{if $article->getReviewAssignments()|@count eq 0}
			<a href="{$requestPageUrl}/submission/{$article->getArticleID()}">{$article->getArticleID()}</a>
		{elseif $article->getDecisions()|@count eq 0}
			<a href="{$requestPageUrl}/submissionReview/{$article->getArticleID()}">{$article->getArticleID()}</a>
		{elseif $article->getDecisions()|@count gt 0}
			{assign var="toEdit" value="false"}
			{assign var="round" value=$article->getCurrentRound()}
			{foreach from=$article->getDecisions($round) item=editorDecision}
				{if $editorDecision.decision eq $acceptEditorDecisionValue}
					{assign var="toEdit" value="true"}
					{assign var="dateDecided" value=$editorDecision.dateDecided}
				{else}
					{assign var="dateDecided" value=""}
					{assign var="toEdit" value="false"}
				{/if}
			{/foreach}
			{if $toEdit eq "true"}
				<a href="{$requestPageUrl}/submissionEditing/{$article->getArticleID()}">{$article->getArticleID()}</a>
			{else}
				<a href="{$requestPageUrl}/submissionReview/{$article->getArticleID()}">{$article->getArticleID()}</a>
			{/if}
		{/if}
	</td>
	<td>{$article->getSectionTitle()}</a></td>
	<td>
		{foreach from=$article->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td>{$article->getDateSubmitted()|date_format:$dateFormatShort}</td>
	<td>{$dateDecided|date_format:$dateFormatShort}</td>
	<td></td>
	<td></td>
</tr>
{foreachelse}
<tr>
<td colspan="7" class="noResults">{translate key="sectionEditor.noneAssigned"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
