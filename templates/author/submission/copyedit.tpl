{**
 * copyedit.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the copyediting subtable of the author's submission editing page
 *
 *
 * $Id$
 *}

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

