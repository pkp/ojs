{**
 * peerReview.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's peer review table.
 *
 * $Id$
 *}
<a name="peerReview"></a>
<h3>{translate key="submission.peerReview"}</h3>

{assign var=start value="A"|ord}
{section name="round" loop=$submission->getCurrentRound()}
{assign var="round" value=$smarty.section.round.index+1}
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
				<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;{$reviewFile->getDateModified()|date_format:$dateFormatShort}
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
			{foreach from=$viewableFiles item=reviewerFiles key=reviewer}
				{foreach from=$reviewerFiles item=viewableFile key=reviewId}
					{assign var="roundIndex" value=$reviewIndexesByRound[$round][$reviewId]}
					{assign var=thisReviewer value=$start+$roundIndex|chr}
					{translate key="user.role.reviewer"} {$thisReviewer|escape}
					<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$viewableFile->getFileId():$viewableFile->getRevision()}" class="file">{$viewableFile->getFileName()|escape}</a>&nbsp;&nbsp;{$viewableFile->getDateModified()|date_format:$dateFormatShort}<br />
				{/foreach}
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
					<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$editorFile->getDateModified()|date_format:$dateFormatShort}<br />
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
					<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$authorFile->getDateModified()|date_format:$dateFormatShort}<br />
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
