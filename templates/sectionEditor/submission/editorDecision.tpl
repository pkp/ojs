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

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.editorReview"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					<span class="boldText">{translate key="user.role.editor"}:</span>
					{if $editor}
						{$editor->getEditorFullName()}
					{else}
						{translate key="editor.article.noEditorSelected"}
					{/if}	
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td class="reviewLabel" valign="top">
					<span class="boldText">{translate key="editor.article.decision"}</span>
				</td>
				<td colspan="5">
					{foreach from=$submission->getDecisions($round) item=editorDecision key=decisionKey}
						{if $decisionKey neq 0} | {/if}
						{assign var="decision" value=$editorDecision.decision}
						<span class="boldTextAlt">{translate key=$editorDecisionOptions.$decision}</span>
						{$editorDecision.dateDecided|date_format:$dateFormatShort}
					{foreachelse}
						{translate key="common.none"}
					{/foreach}
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="5">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/recordDecision">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<select name="decision" {if not $allowRecommendation}disabled="disabled"{/if}>
								{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
							</select>
							<input type="submit" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if}>
						</form>
					</div>		
				</td>
			</tr>
			<tr>
				<td class="reviewLabel">
					<span class="boldText"><a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}');">{translate key="submission.editorAuthorComments"}</a></span>
				</td>
				<td colspan="5">
					{if $submission->getMostRecentEditorDecisionComment()}
						{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
						<a href="javascript:openComments('{$requestPageUrl}/viewEditorDecisionComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<translate key="common.none"}
					{/if}
				</td>
			</tr>
			<form method="post" action="{$requestPageUrl}/editorReview" enctype="multipart/form-data">
				<tr>
					<td></td>
					<td colspan="2">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="upload">
						<input type="submit" name="submit" value="{translate key="common.upload"}">
					</td>
					<td align="center">
						<input type="submit" name="setCopyeditFile" value="Send to Copyedit" {if not $allowCopyedit}disabled="disabled"{/if}>
					</td>
					<td align="center">
						<input type="submit" name="resubmit" value="Resubmit" {if not $allowResubmit}disabled="disabled"{/if}>
					</td>
				</tr>
				{foreach from=$submission->getEditorFileRevisions($round) item=editorFile key=key}
					<tr>
						<td class="reviewLabel" valign="top">
							{if $key eq 0}
								<span class="boldText">{translate key="submission.editorVersion"}</span>
							{/if}
						</td>
						<td><nobr><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorFile->getFileId()}/{$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a></nobr></td>
						<td>{$editorFile->getDateModified()|date_format:$dateFormatShort}</td>
						<td align="center">
							<input type="radio" name="copyeditFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" {if not $allowCopyedit}disabled="disabled"{/if}>
						</td>
						<td align="center">
							<input type="radio" name="resubmitFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" {if not $allowResubmit}disabled="disabled"{/if}>
						</td>
					</tr>
				{foreachelse}
					<tr>
						<td class="reviewLabel" valign="top">
							<span class="boldText">{translate key="submission.editorVersion"}</span>
						</td>
						<td colspan="4">{translate key="common.none"}</td>
					</tr>
				{/foreach}
				{foreach from=$submission->getAuthorFileRevisions($round) item=authorFile key=key}
					<tr>
						<td class="reviewLabel" valign="top">
							{if $key eq 0}
								<span class="boldText">{translate key="submission.authorVersion"}</span>
							{/if}
						</td>
						<td><nobr><a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$authorFile->getFileId()}/{$authorFile->getRevision()}" class="file">{$authorFile->getFileName()}</a></nobr></td>
						<td>{$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
						<td align="center">
							<input type="radio" name="copyeditFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" {if not $allowCopyedit}disabled="disabled"{/if}>
						</td>
						<td align="center">
							<input type="radio" name="resubmitFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" {if not $allowResubmit}disabled="disabled"{/if}>
						</td>
					</tr>
				{foreachelse}
					<tr>
						<td class="reviewLabel" valign="top">
							<span class="boldText">{translate key="submission.authorVersion"}</span>
						</td>
						<td colspan="4">{translate key="common.none"}</td>
					</tr>
				{/foreach}
			</form>
		</table>
	</td>
</tr>
</table>
</div>
