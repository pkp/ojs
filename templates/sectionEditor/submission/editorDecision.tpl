{**
 * editorDecision.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the editor decision table.
 *
 * $Id$
 *}

<a name="editorDecision"></a>
<h3>{translate key="submission.editorDecision"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td class="label" width="20%">{translate key="editor.article.selectDecision"}</td>
	<td width="80%">
		<form method="post" action="{$requestPageUrl}/recordDecision">
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="editor.article.decision"}</td>
	<td>
		{foreach from=$submission->getDecisions($round) item=editorDecision key=decisionKey}
			{if $decisionKey neq 0} | {/if}
			{assign var="decision" value=$editorDecision.decision}
			{translate key=$editorDecisionOptions.$decision} {$editorDecision.dateDecided|date_format:$dateFormatShort}
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="submission.editorAuthorComments"}</td>
	<td>
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a> {$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
		{/if}
	</td>
</tr>
</table>

<form method="post" action="{$requestPageUrl}/editorReview" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
{assign var=authorFiles value=$submission->getAuthorFileRevisions($round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round)}
{assign var="authorRevisionExists" value=false}
{assign var="editorRevisionExists" value=false}

<table class="data" width="100%">
	{foreach from=$authorFiles item=authorFile key=key}
		<tr valign="top">
			{if !$authorRevisionExists}
				{assign var="authorRevisionExists" value=true}
				<td width="20%" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
			{/if}
			<td width="30%"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
			<td width="10%">&nbsp;</td>
			{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
				<td width="30%">{translate key="editor.article.sendToCopyedit"}</td>
				<td width="10%">
					<input type="radio" name="copyeditFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" />
				</td>
			{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
				<td width="30%">{translate key="editor.article.resubmitForReview"}</td>
				<td width="10%">
					<input type="radio" name="resubmitFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" />
				</td>
			{else}
				<td colspan="2">&nbsp;</td>
			{/if}
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	{foreach from=$editorFiles item=editorFile key=key}
		<tr valign="top">
			{if !$editorRevisionExists}
				{assign var="editorRevisionExists" value=true}
				<td width="20%" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
			{/if}

			<td width="30%"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a> {$editorFile->getDateModified()|date_format:$dateFormatShort}</td>
			<td width="10%"><a href="{$requestPageUrl}/deleteArticleFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
			{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
				<td width="30%">{translate key="editor.article.sendToCopyedit"}</td>
				<td width="10%">
					<input type="radio" name="copyeditFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" />
				</td>
			{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
				<td width="30%">{translate key="editor.article.resubmitForReview"}</td>
				<td width="10%">
					<input type="radio" name="resubmitFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" />
				</td>
			{else}
				<td colspan="2">&nbsp;</td>
			{/if}
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
			<td width="80%" colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
</table>

<div>
	{translate key="editor.article.uploadEditorVersion"}
	<input type="file" name="upload" class="uploadField" />
	<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
</div>

<div class="separator"></div>

{if $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
{translate key="editor.article.resubmitFileForPeerReview"}
<input type="submit" name="resubmit" {if !($editorRevisionExists or authorRevisionExists)}disabled="disabled" {/if}value="{translate key="form.send"}" class="button" />

{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
{translate key="editor.article.sendFileToCopyedit"}
<input type="submit" {if !($editorRevisionExists or authorRevisionExists)}disabled="disabled" {/if}name="setCopyeditFile" value="{translate key="form.send"}" class="button" />
{/if}

</form>
