{**
 * peerReview.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's peer review table.
 *
 * $Id$
 *}

<a name="peerReview"></a>
<h3>{translate key="submission.peerReview"}</h3>

{section name="round" loop=$submission->getCurrentRound()}
{assign var="round" value=$smarty.section.round.index+1}
{assign var="roundIndex" value=$smarty.section.round.index}
{assign var=authorFiles value=$submission->getAuthorFileRevisions($round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round)}
{assign var="viewableFiles" value=$authorViewableFilesByRound[$round]}

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
				{$reviewEarliestNotificationByRound[$round]|date_format:$dateFormatShort}
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
			{if $reviewModifiedByRound[$round]}
				{$reviewModifiedByRound[$round]|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="common.uploadedFile"}
		</td>
		<td class="value" width="80%">
			{foreach from=$viewableFiles item=viewableFile key=key}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$viewableFile->getFileId()}/{$viewableFile->getRevision()}" class="file">{$viewableFile->getFileName()}</a> {$viewableFile->getDateModified()|date_format:$dateFormatShort}<br>
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	{if !$smarty.section.round.last}
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

{if !$smarty.section.round.last}
	<div class="separator"></div>
{/if}

{/section}
