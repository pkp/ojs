{**
 * copyedit.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the copyediting table.
 *
 * $Id$
 *}

<a name="copyedit"></a>
<h3>{translate key="submission.copyediting"}</h3>

{if $useCopyeditors}
<p>{translate key="user.role.copyeditor"}:
{if $submission->getCopyeditorId()}&nbsp; {$copyeditor->getFullName()}{/if}
&nbsp; <a href="{$requestPageUrl}/selectCopyeditor/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectCopyeditor"}</a></p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2"><a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}" class="action">{translate key="submission.reviewMetadata"}</a></td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="18%" class="heading">{translate key="submission.underway"}</td>
		<td width="18%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="23%">{translate key="submission.copyedit.initialCopyedit"}</td>
		<td>{if $submission->getCopyeditorDateNotified()}{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
		<td>{if $submission->getCopyeditorDateUnderway()}{$submission->getCopyeditorDateUnderway()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
		<td>{if $submission->getCopyeditorDateCompleted()}{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
	</tr>
	<tr>
		<td></td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $initialCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator"></td>
	</tr>
	<tr>
		<td>2.</td>
		<td width="20%">{translate key="submission.copyedit.editorAuthorReview"}</td>
		<td>{if $submission->getCopyeditorDateAuthorNotified()}{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
		<td>{if $submission->getCopyeditorDateAuthorUnderway()}{$submission->getCopyeditorDateAuthorUnderway()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
		<td>{if $submission->getCopyeditorDateAuthorCompleted()}{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort}{else}&mdash{/if}</td>

	</tr>
	<tr>
		<td></td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $editorAuthorCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator"></td>
	</tr>
	<tr>
		<td>3.</td>
		<td width="20%">{translate key="submission.copyedit.finalCopyedit"}</td>
		<td>{if $submission->getCopyeditorDateFinalNotified()}{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
		<td>{if $submission->getCopyeditorDateFinalUnderway()}{$submission->getCopyeditorDateFinalUnderway()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
		<td>{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}{else}&mdash{/if}</td>
	</tr>
	<tr>
		<td></td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $finalCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator"></td>
	</tr>
	<tr valign="top">
		<td colspan="5">
			<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				{translate key="common.file"}:&nbsp;
				{if $submission->getCopyeditorDateAuthorNotified() and $editorAuthorCopyeditFile}
					<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
				{else}
					{translate key="common.none"}
				{/if}
				<br />
				{translate key="author.submissions.uploadCopyeditedVersion"}
				&nbsp;
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="hidden" name="copyeditStage" value="author" />
				<input type="file" name="upload"{if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()} disabled="disabled"{/if} class="uploadField" />
				<input type="submit" class="button" value="{translate key="common.upload"}"{if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()} disabled="disabled"{/if} />
			</form>
			<form method="post" action="{$requestPageUrl}/completeAuthorCopyedit">
				<input type="submit" class="button" value="{translate key="submission.complete"}" {if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()}disabled="disabled"{/if}>
			</form>
		</td>
	</tr>
</table>

<p>{translate key="submission.copyedit.copyeditComments"}
{if $submission->getMostRecentCopyeditComment()}
	{assign var="comment" value=$submission->getMostRecentCopyeditComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}</p>
