{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 *
 * $Id$
 *}

{assign_translate var="pageTitleTranslated" key="submission.page.editing" id=$submission->getArticleId()}
{assign var="pageCrumbTitle" value="submission.editing"}

{include file="common/header.tpl"}

{include file="copyeditor/submission/summary.tpl"}

<div class="separator"></div>

<h3>{translate key="submission.copyedit"}</h3>

<table width="100%" class="info">
	<tr valign="top">
		<td colspan="6">
			{if $submission->getCopyeditorId()}
				<span class="boldText">{translate key="user.role.copyeditor"}:</span> {$copyeditor->getFullName()}
			{else}
				<a href="{$requestPageUrl}/selectCopyeditor/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectCopyeditor"}</a>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td colspan="2"><a class="action" href="{$pageUrl}/copyeditor/viewMetadata/{$submission->getArticleId()}">{translate key="submission.viewMetadata"}</a></td>
		<td class="heading" width="15%">{translate key="submission.request"}</td>
		<td class="heading" width="15%">{translate key="submission.underway"}</td>
		<td class="heading" width="15%">{translate key="submission.complete"}</td>
		<td class="heading" width="15%">{translate key="submission.thank"}</td>
	</tr>
<!-- START INITIAL COPYEDIT -->
	<tr valign="top">
		<td width="5%">1.</td>
		<td width="35%">
			{translate key="submission.copyedit.initialCopyedit"}<br/>
			{if $submission->getDateNotified() and $initialCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
		<td width="15%">{if $submission->getDateNotified()}{$submission->getDateNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">{if $submission->getDateUnderway()}{$submission->getDateUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">
			{if not $submission->getDateNotified() or $submission->getDateCompleted()}
				{icon name="mail" disabled="disabled" url="$requestPageUrl/completeCopyedit?articleId=`$submission->getArticleId()`"}
			{else}
				{icon name="mail" url="$requestPageUrl/completeCopyedit?articleId=`$submission->getArticleId()`"}
			{/if}
			{if $submission->getDateCompleted()}{$submission->getDateCompleted()|date_format:$dateFormatShort}{/if}
		</td>
		<td width="15%">{if $submission->getDateAcknowledged()}{$submission->getDateAcknowledged()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td width="5%"></td>
		<td colspan="5" width="95%">
			<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="hidden" name="copyeditStage" value="initial">
				<input type="file" class="button" name="upload" {if not $submission->getDateNotified() or $submission->getDateCompleted()}disabled="disabled"{/if}>
				<input type="submit" class="button" value="{translate key="common.upload"}" {if not $submission->getDateNotified() or $submission->getDateCompleted()}disabled="disabled"{/if}>
			</form>
		</td>
	</tr>
<!-- END INITIAL COPYEDIT -->
<!-- START AUTHOR COPYEDIT -->
	<tr valign="top">
		<td width="5%">2. </td>
		<td width="35%">
			{translate key="submission.copyedit.editorAuthorReview"}<br/>
			{if $editorAuthorCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
		<td width="15%">{if $submission->getDateAuthorNotified()}{$submission->getDateAuthorNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">{if $submission->getDateAuthorUnderway()}{$submission->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">{if $submission->getDateAuthorCompleted()}{$submission->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">{if $submission->getDateAuthorAcknowledged()}{$submission->getDateAuthorAcknowledged()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	</tr>
<!-- END AUTHOR COPYEDIT REVIEW -->
<!-- START FINAL COPYEDIT -->
	<tr valign="top">
		<td width="5%">3. </td>
		<td width="35%">
			{translate key="submission.copyedit.finalCopyedit"}<br/>
			{if $submission->getDateFinalNotified() and $finalCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
		<td width="15%">{if $submission->getDateFinalNotified()}{$submission->getDateFinalNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">{if $submission->getDateFinalUnderway()}{$submission->getDateFinalUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td width="15%">
			{if not $submission->getDateFinalNotified() or $submission->getDateFinalCompleted()}
				{icon name="mail" url="$requestPageUrl/completeFinalCopyedit?articleId=`$submission->getArticleId()`" disabled="disabled"}
			{else}
				{icon name="mail" url="$requestPageUrl/completeFinalCopyedit?articleId=`$submission->getArticleId()`"}
			{/if}
			{if $submission->getDateFinalCompleted()}{$submission->getDateFinalCompleted()|date_format:$dateFormatShort}{/if}
		</td>
		<td width="15%">{if $submission->getDateFinalAcknowledged()}{$submission->getDateFinalAcknowledged()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td width="5%"></td>
		<td colspan="5" width="95%">
			<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="hidden" name="copyeditStage" value="final">
				<input type="file" class="button" name="upload" {if not $submission->getDateFinalNotified() or $submission->getDateFinalCompleted()}disabled="disabled"{/if}>
				<input type="submit" class="button" value="{translate key="common.upload"}" {if not $submission->getDateFinalNotified() or $submission->getDateFinalCompleted()}disabled="disabled"{/if}>
			</form>
		</td>
	</tr>
<!-- END FINAL COPYEDIT -->
</table>
<p>{translate key="submission.copyedit.copyeditComments"}
{if $submission->getMostRecentCopyeditComment()}
	{assign var="comment" value=$submission->getMostRecentCopyeditComment()}        <a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}</p>

<div class="separator"></div>

{include file="copyeditor/submission/layout.tpl"}

{include file="common/footer.tpl"}
