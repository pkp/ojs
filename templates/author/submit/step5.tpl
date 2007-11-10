{**
 * step5.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of author article submission.
 *
 * $Id$
 *}
{assign var="pageTitle" value="author.submit.step5"}
{include file="author/submit/submitHeader.tpl"}

<p>{translate key="author.submit.confirmationDescription" journalTitle=$journal->getJournalTitle()}</p>

<form method="post" action="{url op="saveSubmit" path=$submitStep}">
<input type="hidden" name="articleId" value="{$articleId}" />
{include file="common/formErrors.tpl"}

<h3>{translate key="author.submit.filesSummary"}</h3>
<table class="listing" width="100%">
<tr>
	<td colspan="5" class="headseparator">&nbsp;</td>
</tr>
<tr class="heading" valign="bottom">
	<td width="10%">{translate key="common.id"}</td>
	<td width="35%">{translate key="common.originalFileName"}</td>
	<td width="25%">{translate key="common.type"}</td>
	<td width="20%" class="nowrap">{translate key="common.fileSize"}</td>
	<td width="10%" class="nowrap">{translate key="common.dateUploaded"}</td>
</tr>
<tr>
	<td colspan="5" class="headseparator">&nbsp;</td>
</tr>
{foreach from=$files item=file}
<tr valign="top">
	<td>{$file->getFileId()}</td>
	<td><a class="file" href="{url op="download" path=$articleId|to_array:$file->getFileId()}">{$file->getOriginalFileName()|escape}</a></td>
	<td>{if ($file->getType() == 'supp')}{translate key="article.suppFile"}{else}{translate key="author.submit.submissionFile"}{/if}</td>
	<td>{$file->getNiceFileSize()}</td>
	<td>{$file->getDateUploaded()|date_format:$dateFormatTrunc}</td>
</tr>
{foreachelse}
<tr valign="top">
<td colspan="5" class="nodata">{translate key="author.submit.noFiles"}</td>
</tr>
{/foreach}
</table>

<div class="separator"></div>

{if $authorFees}
	{include file="author/submit/authorFees.tpl" showPayLinks=1}
	{if $currentJournal->getLocalizedSetting('waiverPolicy') != ''}
		{if $manualPayment}
			<h3>{translate key="payment.alreadyPaid"}</h3>
			<table class="data" width="100%">
				<tr valign="top">
				<td width="5%" align="left"><input type="checkbox" name="paymentSent" value="1" /></td>
				<td width="95%">{translate key="payment.paymentSent"}</td>
				</tr>
				<tr>
				<td />
				<td>{translate key="payment.alreadyPaidMessage"}</td>
				<tr>
			</table>
		{/if}
		<h3>{translate key="author.submit.requestWaiver"}</h3>
		<table class="data" width="100%">
			<tr valign="top">
				<td width="5%" align="left"><input type="checkbox" name="qualifyForWaiver" value="1" /></td>
				<td width="95%">{translate key="author.submit.qualityForWaiver"}</td>
			</tr>
			<tr>
				<td />
				<td>
					<label for="commentsToEditor">{translate key="author.submit.addReasonsForWaiver"}</label><br />
					<textarea name="commentsToEditor" id="commentsToEditor" rows="3" cols="40" class="textArea">{$commentsToEditor|escape}</textarea>
				</td>
			</tr>
		</table> 
	{/if}
{/if}

<div class="separator"></div>

<p><input type="submit" value="{translate key="author.submit.finishSubmission"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="confirmAction('{url page="author"}', '{translate|escape:"jsparam" key="author.submit.cancelSubmission"}')" /></p>

</form>

{include file="common/footer.tpl"}
