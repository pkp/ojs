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
		<table class="plain" width="100%">
			<tr>
				<td width="30%"></td>
				<td width="25%"></td>
				<td width="15%" align="center"><strong>{translate key="submission.request"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="submission.complete"}</strong></td>
				<td width="15%" align="center"><strong>{translate key="submission.thank"}</strong></td>
			</tr>
			<tr>
				<td width="30%">
					<span class="boldText">1.</span>
					{translate key="submission.initialCopyedit"}
				</td>
				<td width="25%" align="right">
					{if not $submission->getDateCompleted()}
						<form method="post" action="{$pageUrl}/copyeditor/completeCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="copyeditor.article.complete"}">
						</form>
					{/if}
				</td>
				<td width="15%" align="center">{$submission->getDateNotified()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{$submission->getDateCompleted()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{$submission->getDateAcknowledged()|date_format:$dateFormatShort}</td>
			</tr>
			<tr>
				<td width="30%">
					<span class="boldText">2.</span>
					{translate key="submission.editorAuthorReview"}
				</td>
				<td width="25%" align="right"></td>
				<td width="15%" align="center">{$submission->getDateAuthorNotified()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{$submission->getDateAuthorCompleted()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{$submission->getDateAuthorAcknowledged()|date_format:$dateFormatShort}</td>
			</tr>
			<tr>
				<td width="30%">
					<span class="boldText">3.</span>
					{translate key="submission.finalCopyedit"}
				</td>
				<td width="25%" align="right">
					{if $submission->getDateAuthorCompleted() and $submission->getDateFinalNotified() and not $submission->getDateFinalCompleted()}
						<form method="post" action="{$pageUrl}/copyeditor/completeFinalCopyedit">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="copyeditor.article.complete"}">
						</form>
					{/if}
				</td>
				<td width="15%" align="center">{$submission->getDateFinalNotified()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{$submission->getDateFinalCompleted()|date_format:$dateFormatShort}</td>
				<td width="15%" align="center">{$submission->getDateFinalAcknowledged()|date_format:$dateFormatShort}</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					{translate key="submission.copyeditVersion"}:
					{if not $submission->getDateCompleted()}
						{if $initialRevisionFile}
							<a href="{$pageUrl}/copyeditor/downloadFile/{$initialRevisionFile->getFileId()}/{$initialRevisionFile->getRevision()}" class="file">{$initialRevisionFile->getFileName()}</a> {$initialRevisionFile->getDateModified()|date_format:$dateFormatShort}
						{else}
							{translate key="common.none"}
						{/if}
					{elseif $submission->getDateAuthorCompleted() and not $submission->getDateFinalCompleted()}
						{if $finalRevisionFile}
							<a href="{$pageUrl}/copyeditor/downloadFile/{$finalRevisionFile->getFileId()}/{$finalRevisionFile->getRevision()}" class="file">{$finalRevisionFile->getFileName()}</a> {$finalRevisionFile->getDateModified()|date_format:$dateFormatShort}
						{else}
							{translate key="common.none"}
						{/if}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
				<td>
					<form method="post" action="{$pageUrl}/copyeditor/uploadCopyeditVersion" enctype="multipart/form-data">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
						<input type="file" name="upload">
						<input type="submit" name="submit" value="{translate key="common.upload"}">
					</form>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
