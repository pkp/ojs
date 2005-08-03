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
{if $submission->getCopyeditorId()}&nbsp; {$copyeditor->getFullName()|escape}{else}{translate key="common.none"}{/if}</p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2"><a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}" class="action">{translate key="submission.reviewMetadata"}</a></td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="35%">{translate key="submission.copyedit.initialCopyedit"}</td>
		<td>{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$submission->getCopyeditorDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="4">
			{translate key="common.file"}:
			{if $submission->getCopyeditorDateNotified() && $initialCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()|escape}</a>&nbsp;&nbsp;{$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td>2.</td>
		<td>{translate key="submission.copyedit.editorAuthorReview"}</td>
		<td>{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$submission->getCopyeditorDateAuthorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>
			{if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{icon name="mail" url="$requestPageUrl/completeAuthorCopyedit?articleId=`$submission->getArticleId()`"}
			{/if}
			{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $submission->getCopyeditorDateAuthorNotified() && $editorAuthorCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()|escape}</a>&nbsp;&nbsp;{$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
			<br />
			<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
				<input type="hidden" name="copyeditStage" value="author" />
				<input type="file" name="upload"{if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()} disabled="disabled"{/if} class="uploadField" />
				<input type="submit" class="button" value="{translate key="common.upload"}"{if not $submission->getCopyeditorDateAuthorNotified() or $submission->getCopyeditorDateAuthorCompleted()} disabled="disabled"{/if} />
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td>3.</td>
		<td>{translate key="submission.copyedit.finalCopyedit"}</td>
		<td>{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$submission->getCopyeditorDateFinalUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="4">
			{translate key="common.file"}:
			{if $submission->getCopyeditorDateFinalNotified() && $finalCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()|escape}</a>&nbsp;&nbsp;{$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.copyedit.copyeditComments"}
{if $submission->getMostRecentCopyeditComment()}
	{assign var="comment" value=$submission->getMostRecentCopyeditComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}

{if $currentJournal->getSetting('copyeditInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{$requestPageUrl}/instructions/copy')" class="action">{translate key="submission.copyedit.instructions"}</a>
{/if}
