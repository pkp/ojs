{**
 * templates/author/submission/authorFees.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display of author fees and payment information
 *
 *}
<div id="authorFees">
<h3>{translate key="payment.authorFees"}</h3>
<table width="100%" class="data">
{if $currentJournal->getSetting('submissionFeeEnabled')}
	<tr>
		<td width="20%">{$currentJournal->getLocalizedSetting('submissionFeeName')|escape}</td>
	{if $submissionPayment}
		<td width="80%" colspan="2">{translate key="payment.paid"} {$submissionPayment->getTimestamp()|date_format:$datetimeFormatLong}</td>
	{else}
		<td width="30%">{$currentJournal->getSetting('submissionFee')|string_format:"%.2f"} {$currentJournal->getSetting('currency')}</td> 
		<td width="50%"><a class="action" href="{url op="paySubmissionFee" path=$submission->getId()}">{translate key="payment.payNow"}</a></td>
	{/if}
	</tr>
{/if}
{if $currentJournal->getSetting('fastTrackFeeEnabled')}
	<tr>
		<td width="20%">{$currentJournal->getLocalizedSetting('fastTrackFeeName')|escape}: 
	{if $fastTrackPayment}
		<td width="80%" colspan="2">{translate key="payment.paid"} {$fastTrackPayment->getTimestamp()|date_format:$datetimeFormatLong}</td>
	{else}
		<td width="30%">{$currentJournal->getSetting('fastTrackFee')|string_format:"%.2f"} {$currentJournal->getSetting('currency')}</td>
		<td width="50%"><a class="action" href="{url op="payFastTrackFee" path=$submission->getId()}">{translate key="payment.payNow"}</a></td>
	{/if}
	</tr>	
{/if}
{if $currentJournal->getSetting('publicationFeeEnabled')}
	<tr>
		<td width="20%">{$currentJournal->getLocalizedSetting('publicationFeeName')|escape}</td>
	{if $publicationPayment}
		<td width="80%" colspan="2">{translate key="payment.paid"} {$publicationPayment->getTimestamp()|date_format:$datetimeFormatLong}</td>
	{else}
		<td width="30%">{$currentJournal->getSetting('publicationFee')|string_format:"%.2f"} {$currentJournal->getSetting('currency')}</td>
		<td width="50%"><a class="action" href="{url op="payPublicationFee" path=$submission->getId()}">{translate key="payment.payNow"}</a></td>
	{/if}
	</tr>	
{/if}
</table>
</div>
