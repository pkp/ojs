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
		<form method="post" action="{url op="recordDecision"}">
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"javascript" key="editor.submissionReview.confirmDecision"}')" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
			{if not $allowRecommendation}&nbsp;&nbsp;{translate key="editor.article.cannotRecord}{/if}
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="editor.article.decision"}</td>
	<td class="value" colspan="2">
		{foreach from=$submission->getDecisions($round) item=editorDecision key=decisionKey}
			{if $decisionKey neq 0} | {/if}
			{assign var="decision" value=$editorDecision.decision}
			{translate key=$editorDecisionOptions.$decision}&nbsp;&nbsp;{$editorDecision.dateDecided|date_format:$dateFormatShort}
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="submission.notifyAuthor"}</td>
	<td class="value" colspan="2">
		{url|assign:"notifyAuthorUrl" op="emailEditorDecisionComment" articleId=$submission->getArticleId()}
		{icon name="mail" url=$notifyAuthorUrl}
		&nbsp;&nbsp;&nbsp;&nbsp;
		{translate key="submission.editorAuthorRecord"}
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getArticleId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
		{/if}
	</td>
</tr>
</table>

<form method="post" action="{url op="editorReview"}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
{assign var=authorFiles value=$submission->getAuthorFileRevisions($round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round)}

{assign var="authorRevisionExists" value=false}
{foreach from=$authorFiles item=authorFile}
	{assign var="authorRevisionExists" value=true}
{/foreach}

{assign var="editorRevisionExists" value=false}
{foreach from=$editorFiles item=editorFile}
	{assign var="editorRevisionExists" value=true}
{/foreach}


{if $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
{translate key="editor.article.resubmitFileForPeerReview"}
<input type="submit" name="resubmit" {if !($editorRevisionExists or $authorRevisionExists)}disabled="disabled" {/if}value="{translate key="form.resubmit"}" class="button" />

{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
{translate key="editor.article.sendFileToCopyedit"}
<input type="submit" {if !($editorRevisionExists or $authorRevisionExists)}disabled="disabled" {/if}name="setCopyeditFile" value="{translate key="form.send"}" class="button" />
{/if}

<table class="data" width="100%">
	{assign var="firstItem" value=true}
	{foreach from=$editorFiles item=editorFile key=key}
		<tr valign="top">
			{if $firstItem}
				{assign var="firstItem" value=false}
				<td width="20%" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
			{/if}
			<td width="50%" class="value" colspan="2">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a>&nbsp;&nbsp;
				{$editorFile->getDateModified()|date_format:$dateFormatShort}
			</td>
			<td width="30%" class="value"><a href="{url op="deleteArticleFile" path=$submission->getArticleId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
			<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	{assign var="firstItem" value=true}
	{foreach from=$authorFiles item=authorFile key=key}
		<tr valign="top">
			{if $firstItem}
				{assign var="firstItem" value=false}
				<td width="20%" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
			{/if}
			<td width="80%" class="value" colspan="3">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getArticleId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a>&nbsp;&nbsp;
				{$authorFile->getDateModified()|date_format:$dateFormatShort}
			</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}

	<td class="label">{translate key="editor.article.uploadEditorVersion"}</td>
	<td class="value">
		<input type="file" name="upload" class="uploadField" />
		<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
	</td>
</table>

</form>
