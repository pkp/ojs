{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission.
 *
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.editing" id=$submission->getArticleId()}
{assign var="pageId" value="author.submissionEditing"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.review"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.editing"}</a></li>
</ul>

<h3>{translate key="submission.submission"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.authors"}</td>
		<td width="80%" class="data">{$submission->getAuthorString(false)}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.title"}</td>
		<td width="80%" class="data">{$submission->getArticleTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="section.section"}</td>
		<td width="80%" class="data">{$submission->getSectionTitle()}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="article.editor"}</td>
		{assign var="editor" value=$submission->getEditor()}
		<td width="80%" class="data">{if ($editor !== null)}{$editor->getEditorFullName()}{else}{translate key="common.none"}{/if}</td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="manager.setup.copyediting"}</h3>

{assign var="copyEditor" value=$submission->getCopyeditor()}
{translate key="copyeditor.journalCopyeditor"}:&nbsp;{if ($editor !== null)}{$editor->getEditorFullName()}{else}{translate key="common.none"}{/if}<br/>

<table width="100%" class="listing">
	<tr><td colspan="5" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="40%" colspan="2">
			<a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}" class="action">{translate key="submission.reviewMetadata"}</a><br/>
		</td>
		<td width="20%">{translate key="submission.request"}</td>
		<td width="20%">{translate key="submission.underway"}</td>
		<td width="20%">{translate key="submission.complete"}</td>
	</tr>
	<tr><td colspan="5" class="headseparator"></td></tr>

<!-- START INITIAL COPYEDIT -->
	<tr valign="top">
		<td width="5%">1.</td>
		<td width="35%">{translate key="submission.copyedit.initialCopyedit"}</td>
		<td>{if $submission->getCopyeditorDateNotified()}{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $submission->getCopyeditorDateUnderway()}{$submission->getCopyeditorDateUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $submission->getCopyeditorDateCompleted()}{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
	</tr>
	<tr valign="top">
		<td></td>
		<td colspan="4">
			{translate key="common.file"}:&nbsp;
			{if $submission->getCopyeditorDateCompleted() and $initialCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
<!-- END INITIAL COPYEDIT -->
	<tr><td colspan="5" class="separator"></td></tr>
<!-- START AUTHOR COPYEDIT -->
	<tr valign="top">
		<td>2.</td>
		<td>{translate key="submission.copyedit.editorAuthorReview"}</td>
		<td>{if $submission->getCopyeditorDateAuthorNotified()}{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $submission->getCopyeditorDateAuthorUnderway()}{$submission->getCopyeditorDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $submission->getCopyeditorDateAuthorCompleted()}{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
	</tr>
	<tr valign="top">
		<td></td>
		<td colspan="4">
			<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				{translate key="common.file"}:&nbsp;
				{if $submission->getCopyeditorDateAuthorNotified() and $editorAuthorCopyeditFile}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
				{else}
					{translate key="common.none"}
				{/if}
				<br/>
				{translate key="author.submissions.uploadCopyeditedVersion"}
				&nbsp;
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="hidden" name="copyeditStage" value="author">
				<input type="file" class="button" name="upload" {if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
				<input type="submit" class="button" value="{translate key="common.upload"}" {if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
			</form>
			<form method="post" action="{$requestPageUrl}/completeAuthorCopyedit">
				<input type="submit" class="button" value="{translate key="submission.complete"}" {if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
			</form>
		</td>
	</tr>
<!-- END AUTHOR COPYEDIT REVIEW -->
	<tr><td colspan="5" class="separator"></td></tr>
<!-- START FINAL COPYEDIT -->
	<tr valign="top">
		<td>3.</td>
		<td>{translate key="submission.copyedit.finalCopyedit"}</td>
		<td align="center" width="15%">{if $submission->getCopyeditorDateFinalNotified()}{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td align="center" width="15%">{if $submission->getCopyeditorDateFinalUnderway()}{$submission->getCopyeditorDateFinalUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td align="center" width="15%">{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
	</tr>
	<tr valign="top">
		<td></td>
		<td colspan="4">
			{translate key="common.file"}:&nbsp;
			{if $submission->getCopyeditorDateFinalCompleted() and $finalCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr><td colspan="5" class="separator"></td></tr>
</table>
<!-- END FINAL COPYEDIT -->
{translate key="submission.copyedit.copyeditComments"}&nbsp;
{if $submission->getMostRecentCopyeditComment()}
	{assign var="comment" value=$submission->getMostRecentCopyeditComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
{/if}
<div class="separator"></div>

<!-- START OF PROOFREADING -->
<h3>{translate key="submission.proofreading"}</h3>
{translate key="user.role.proofreader"}:&nbsp;{if $proofAssignment->getProofreaderId()}{$proofAssignment->getProofreaderFullName()}{else}{translate key="common.none"}{/if}<br/>

<table width="100%" class="listing">
	<tr><td colspan="5" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="40%" colspan="2">{translate key="author.submissions.proofreadingCorrections"}</td>
		<td width="20%">{translate key="submission.request"}</td>
		<td width="20%">{translate key="submission.underway"}</td>
		<td width="20%">{translate key="submission.complete"}</td>
	</tr>
	<tr><td colspan="5" class="headseparator"></td></tr>

<!-- START AUTHOR COMMENTS -->
	<tr valign="top">
		<td width="5%">1.</td>
		<td width="35%">
			{translate key="editor.article.authorProofing"}
			{if $submission->getMostRecentProofreadComment()}
				{assign var="comment" value=$submission->getMostRecentProofreadComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
			{/if}
			<br/>
			<form method="post" action="{$requestPageUrl}/authorProofreadingComplete">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="submit" class="button" value="{translate key="submission.complete"}" {if not $proofAssignment->getDateAuthorNotified() or $proofAssignment->getDateAuthorCompleted()}disabled="disabled"{/if}>
			</form>
		</td>
		<td>{if $proofAssignment->getDateAuthorNotified()}{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $proofAssignment->getDateAuthorUnderway()}{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $proofAssignment->getDateAuthorCompleted()}{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
	</tr>
<!-- END AUTHOR COMMENTS -->
	<tr><td colspan="5" class="separator"></td></tr>
<!-- START PROOFREADER COMMENTS -->
	<tr valign="top">
		<td>2.</td>
		<td>
			{translate key="editor.article.proofreaderComments"}
			{if $submission->getMostRecentProofreadComment()}
				{assign var="comment" value=$submission->getMostRecentProofreadComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
			{/if}
		</td>
		<td>{if $proofAssignment->getDateProofreaderNotified()}{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $proofAssignment->getDateProofreaderUnderway()}{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $proofAssignment->getDateProofreaderCompleted()}{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
	</tr>
<!-- END PROOFREADER COMMENTS -->
	<tr><td colspan="5" class="separator"></td></tr>
<!-- START LAYOUT EDITOR FINAL -->
	<tr valign="top">
		<td>3.</td>
		<td>
			{translate key="editor.article.layoutEditorFinal"}
			{if $submission->getMostRecentProofreadComment()}
				{assign var="comment" value=$submission->getMostRecentProofreadComment()}
				<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
			{/if}	
		</td>
		<td>{if $proofAssignment->getDateLayoutEditorNotified()}{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorUnderway()}{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
	</tr>
	<tr><td colspan="5" class="endseparator"></td></tr>
</table>
<!-- END LAYOUT EDITOR FINAL -->
<!-- END OF PROOFREADING -->

{include file="common/footer.tpl"}
