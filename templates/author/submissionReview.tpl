{**
 * submissionReview.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Author's submission review.
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.review" id=$submission->getArticleId()}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
</ul>

{include file="author/submission/summary.tpl"}

<div class="separator"></div>

<a name="peerReview"></a>
<h3>{translate key="submission.peerReview"}</h3>

{section name="round" loop=$submission->getCurrentRound()}
{assign var="round" value=$smarty.section.round.index+1}
{assign var="roundIndex" value=$smarty.section.round.index}
{assign var=authorFiles value=$submission->getAuthorFileRevisions($round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round)}

<h4>{translate key="submission.round" round=$round}</h4>

<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.reviewVersion"}
		</td>
		<td class="value" width="80%">
			{assign var="reviewFile" value=$reviewFilesByRound[$round]}
			{if $reviewFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$reviewFile->getFileId()}/{$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a> {$reviewFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.initiated"}
		</td>
		<td class="value" width="80%">
			{if $reviewEarliestNotificationByRound[$round]}
				{$reviewEarliestNotificationByRound[$round]}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.lastModified"}
		</td>
		<td class="value" width="80%">
			FIXME
		</td>
	</tr>
	{if !$smarty.section.round.last}
		<tr valign="top">
			<td class="label" width="20%">
				{translate key="common.uploadedFile"}
			</td>
			<td class="value" width="80%">
				FIXME
			</td>
		</tr>
		<tr valign="top">
			<td class="label" width="20%">
				{translate key="submission.editorVersion"}
			</td>
			<td class="value" width="80%">
				{foreach from=$editorFiles item=editorFile key=key}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a> {$editorFile->getDateModified()|date_format:$dateFormatShort}<br>
				{foreachelse}
					{translate key="common.none"}
				{/foreach}
			</td>
		</tr>
		<tr valign="top">
			<td class="label" width="20%">
				{translate key="submission.authorVersion"}
			</td>
			<td class="value" width="80%">
				{foreach from=$authorFiles item=authorFile key=key}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}
				{foreachelse}
					{translate key="common.none"}
				{/foreach}
			</td>
		</tr>
	{/if}
</table>

<div class="separator"></div>

{/section}

<h3>{translate key="submission.editorDecision"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.editorAuthorComments"}
		</td>
		<td class="value" width="80%">
			{if $submission->getMostRecentEditorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.editorVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$editorFiles item=editorFile key=key}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a> {$editorFile->getDateModified()|date_format:$dateFormatShort}<br>
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.authorVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$authorFiles item=authorFile key=key}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="{$requestPageUrl}/deleteArticleFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="action">{translate key="common.delete"}</a>
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="author.article.uploadAuthorVersion"}
		</td>
		<td class="value" width="80%">
			<form method="post" action="{$requestPageUrl}/uploadRevisedVersion" enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="file" name="upload" class="uploadField" />
				<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
			</form>

		</td>
	</tr>
</table>
{include file="common/footer.tpl"}
