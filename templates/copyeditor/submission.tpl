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

<div class="formSectionTitle">{translate key="submission.submission"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td class="formLabel">{translate key="article.title"}:</td>
	<td>{$submission->getTitle()}</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.authors"}:</td>
	<td>
		{foreach from=$submission->getAuthors() item=author}
			<div>{$author->getFullName()}</div>
		{/foreach}
	</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.indexingInformation"}:</td>
	<td>[<a href="{$pageUrl}/copyeditor/viewMetadata/{$submission->getArticleId()}">{translate key="article.metadata"}</a>]</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="article.section"}:</td>
	<td>{$submission->getSectionTitle()}</td>
	<td>&nbsp;</td>
</tr>
</table>
</div>

<br />
<br />

<div class="formSectionTitle">{translate key="submission.copyedit"}</div>
<div class="formSection">
<table class="form" width="100%">
<tr>
	<td width="5%"></td>
	<td width="25%"></td>
	<td width="25%"></td>
	<td width="15%" class="label">{translate key="submission.request"}</td>
	<td width="15%" class="label">{translate key="submission.complete"}</td>
	<td width="15%" class="label">{translate key="submission.thank"}</td>
</tr>
<tr>
	<td width="5%">1.</td>
	<td width="25%">{translate key="submission.initialCopyedit"}</td>
	<td width="25%" align="right">
		{if not $submission->getDateNotified()}
			<form method="post" action="{$pageUrl}/copyeditor/completeCopyedit">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="submit" value="{translate key="copyeditor.article.complete"}">
			</form>
		{/if}
	</td>
	<td width="15%">{$submission->getDateNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getDateCompleted()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getDateAcknowledged()|date_format:$dateFormatShort}</td>
</tr>
<tr>
	<td width="5%">2.</td>
	<td width="25%">{translate key="submission.editorAuthorReview"}</td>
	<td width="25%" align="right"></td>
	<td width="15%">{$submission->getDateAuthorNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getDateAuthorCompleted()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getDateAuthorAcknowledged()|date_format:$dateFormatShort}</td>
</tr>
<tr>
	<td width="5%">3.</td>
	<td width="25%">{translate key="submission.finalCopyedit"}</td>
	<td width="25%" align="right">
		{if $submission->getDateAuthorCompleted() and $submission->getDateFinalNotified() and not $submission->getDateFinalCompleted()}
			<form method="post" action="{$pageUrl}/copyeditor/completeFinalCopyedit">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="submit" value="{translate key="copyeditor.article.complete"}">
			</form>
		{/if}
	</td>
	<td width="15%">{$submission->getDateFinalNotified()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getDateFinalCompleted()|date_format:$dateFormatShort}</td>
	<td width="15%">{$submission->getDateFinalAcknowledged()|date_format:$dateFormatShort}</td>
</tr>
<tr>
	<td colspan="3">{translate key="submission.copyeditVersion"}:</td>
	<td colspan="3">
		<form method="post" action="">
			<input type="file" name="upload">
			<input type="submit" value="{translate key="common.upload"}">
		</form>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
