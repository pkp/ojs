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
	<td width="80%" class="value" colspan="2">
		<form method="post" action="{$requestPageUrl}/recordDecision">
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"javascript" key="editor.submissionReview.confirmDecision"}')" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="editor.article.decision"}</td>
	<td class="value" colspan="2">
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
	<td class="value" colspan="2">
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
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
			<td width="25%" class="value">{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" /> {/if}<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a></td>
			<td width="55%" colspan="2">{$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	{foreach from=$editorFiles item=editorFile key=key}
		<tr valign="top">
			{if !$editorRevisionExists}
				{assign var="editorRevisionExists" value=true}
				<td width="20%" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
			{/if}
			<td width="25%" class="value">{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /> {/if}<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a></td>
			<td width="25%" class="value">{$editorFile->getDateModified()|date_format:$dateFormatShort}</td>
			<td width="30%" class="value"><a href="{$requestPageUrl}/deleteArticleFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
			<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
</table>

<div>
	{translate key="editor.article.uploadEditorVersion"}
	<input type="file" name="upload" class="uploadField" />
	<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
</div>

{if $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
{translate key="editor.article.resubmitFileForPeerReview"}
<input type="submit" name="resubmit" {if !($editorRevisionExists or $authorRevisionExists)}disabled="disabled" {/if}value="{translate key="form.resubmit"}" class="button" />

{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
{translate key="editor.article.sendFileToCopyedit"}
<input type="submit" {if !($editorRevisionExists or $authorRevisionExists)}disabled="disabled" {/if}name="setCopyeditFile" value="{translate key="form.send"}" class="button" />
{/if}

<div class="separator"></div>

</form>
