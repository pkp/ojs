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

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td valign="top">{translate key="article.indexingInformation"}: <a href="{$pageUrl}/copyeditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a></td>
				<td valign="top">{translate key="article.section"}: {$submission->getSectionTitle()}</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.copyedit"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					{if $submission->getCopyeditorId()}
						<span class="boldText">{translate key="user.role.copyeditor"}:</span> {$copyeditor->getFullName()}
					{else}
						<form method="post" action="{$requestPageUrl}/selectCopyeditor/{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.selectCopyeditor"}">
						</form>
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="reviewDivider">
	<td></td>
</tr>
<!-- START INITIAL COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">1. {translate key="submission.initialCopyedit"}</td>
				<td width="20%">
					{if $submission->getDateNotified() and $initialCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/completeCopyedit">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.complete"}" {if not $submission->getDateNotified() or $submission->getDateCompleted()}disabled="disabled"{/if}>
					</form>
				</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.thank"}</strong></td>
			</tr>
			<tr>
				<td colspan="2" width="40%">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="hidden" name="copyeditStage" value="initial">
							<input type="file" name="upload" {if not $submission->getDateNotified() or $submission->getDateCompleted()}disabled="disabled"{/if}>
							<input type="submit" value="{translate key="common.upload"}" {if not $submission->getDateNotified() or $submission->getDateCompleted()}disabled="disabled"{/if}>
						</form>
					</div>			
				</td>
				<td align="center" width="15%">{if $submission->getDateNotified()}{$submission->getDateNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateUnderway()}{$submission->getDateUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateCompleted()}{$submission->getDateCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateAcknowledged()}{$submission->getDateAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END INITIAL COPYEDIT -->
<!-- START AUTHOR COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">2. {translate key="submission.editorAuthorReview"}</span></td>
				<td width="20%">
					{if $editorAuthorCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.thank"}</strong></td>
			</tr>
			<tr>
				<td colspan="2" width="40%"></td>
				<td align="center" width="15%">{if $submission->getDateAuthorNotified()}{$submission->getDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateAuthorUnderway()}{$submission->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateAuthorCompleted()}{$submission->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateAuthorAcknowledged()}{$submission->getDateAuthorAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END AUTHOR COPYEDIT REVIEW -->
<!-- START FINAL COPYEDIT -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="20%"><span class="boldText">3. {translate key="submission.finalCopyedit"}</td>
				<td width="20%">
					{if $submission->getDateFinalNotified() and $finalCopyeditFile}
						<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/completeFinalCopyedit">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.complete"}" {if not $submission->getDateFinalNotified()}disabled="disabled"{/if}>
					</form>
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.thank"}</strong></td>
			</tr>
			<tr>
				<td colspan="2" width="40%">
					<div class="indented">
						<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="hidden" name="copyeditStage" value="final">
							<input type="file" name="upload" {if not $submission->getDateFinalNotified()}disabled="disabled"{/if}>
							<input type="submit" value="{translate key="common.upload"}" {if not $submission->getDateFinalNotified()}disabled="disabled"{/if}>
						</form>
					</div>			
				</td>
				<td align="center" width="15%">{if $submission->getDateFinalNotified()}{$submission->getDateFinalNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateFinalUnderway()}{$submission->getDateFinalUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateFinalCompleted()}{$submission->getDateFinalCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $submission->getDateFinalAcknowledged()}{$submission->getDateFinalAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END FINAL COPYEDIT -->
<tr class="reviewDivider">
	<td></td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
