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
<tr>
	<td class="label" width="20%">{translate key="editor.article.selectDecision"}</td>
	<td width="80%">
		<form method="post" action="{$requestPageUrl}/recordDecision">
			<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
			<select name="decision" {if not $allowRecommendation}disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button">
		</form>
	</td>
</tr>
<tr>
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
<tr>
	<td class="label">{translate key="submission.editorAuthorComments"}</td>
	<td>
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a> {$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			{translate key="common.none"}
		{/if}
	</td>
</tr>
</table>

<form method="post" action="{$requestPageUrl}/editorReview" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
<table>
<tr>
	<td class="label" valign="top">{translate key="submission.authorVersion"}</td>
	<td>
		<table class="data" width="100%">
		{foreach from=$submission->getAuthorFileRevisions($round) item=authorFile key=key}
			<tr>
				<td width="40%"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a> {$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
				<td width="15%">&nbsp;</td>
				<td width="35%">{translate key="editor.article.resubmitForReview"}</td>
				<td width="10%"><input type="radio" name="resubmitFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" /></td>
			</tr>
			<tr>
				<td colspan="2"></td>
				<td>{translate key="editor.article.sendToCopyedit"}</td>
				<td><input type="radio" name="copyeditFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" /></td>
			</tr>
		{foreachelse}
			<tr>
				<td>{translate key="common.none"}</td>
			</tr>
		{/foreach}
		</table>
	</td>
</tr>
<tr>
	<td class="label" valign="top">{translate key="submission.editorVersion"}</td>
	<td>
		<table class="data" width="100%">
		{foreach from=$submission->getEditorFileRevisions($round) item=editorFile key=key}
			<tr>
				<td width="40%"><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a> {$editorFile->getDateModified()|date_format:$dateFormatShort}</td>
				<td width="15%"><a href="{$requestPageUrl}/deleteArticleFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
				<td width="35%">{translate key="editor.article.resubmitForReview"}</td>
				<td width="10%"><input type="radio" name="resubmitFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /></td>
			</tr>
			<tr>
				<td colspan="2"></td>
				<td>{translate key="editor.article.sendToCopyedit"}</td>
				<td><input type="radio" name="copyeditFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /></td>
			</tr>
		{foreachelse}
			<tr>
				<td>{translate key="common.none"}</td>
			</tr>
		{/foreach}
		</table>
		
		<div>
			{translate key="editor.article.uploadEditorVersion"}
			<input type="file" name="upload" class="button">
			<input type="submit" name="submit" value="{translate key="common.upload"}" class="button">
		</div>
	</td>
</tr>
</table>

<div class="separator"></div>

{translate key="editor.article.resubmitFileForPeerReview"}
<input type="submit" name="resubmit" value="{translate key="form.send"}" class="button" />

<br />

{translate key="editor.article.sendFileToCopyedit"}
<input type="submit" name="setCopyeditFile" value="{translate key="form.send"}" class="button" />

</form>
