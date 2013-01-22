{**
 * templates/author/submission/authorFees.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display of author fees and payment information
 *
 *}
<div id="authorFees">
<h3>{translate key="payment.authorFees"}</h3>
<table class="data">
{if $currentJournal->getSetting('submissionFeeEnabled')}
	<tr>
		<td>{$currentJournal->getLocalizedSetting('submissionFeeName')|escape}</td>
	{if $submissionPayment}
		<td colspan="2">{translate key="payment.paid"} {$submissionPayment->getTimestamp()|date_format:$datetimeFormatLong}</td>
	{else}
		<td>{$currentJournal->getSetting('submissionFee')|string_format:"%.2f"} {$currentJournal->getSetting('currency')}</td> 
		<td><a class="action" href="{url op="paySubmissionFee" path=$submission->getId()}">{translate key="payment.payNow"}</a></td>
	{/if}
 	</tr>
{/if}
{if $currentJournal->getSetting('fastTrackFeeEnabled')}
	<tr>
		<td>{$currentJournal->getLocalizedSetting('fastTrackFeeName')|escape}: 
	{if $fastTrackPayment}
		<td colspan="2">{translate key="payment.paid"} {$fastTrackPayment->getTimestamp()|date_format:$datetimeFormatLong}</td>
	{else}
		<td>{$currentJournal->getSetting('fastTrackFee')|string_format:"%.2f"} {$currentJournal->getSetting('currency')}</td>
		<td><a class="action" href="{url op="payFastTrackFee" path=$submission->getId()}">{translate key="payment.payNow"}</a></td>
	{/if}
 	</tr>	
{/if}
{if $currentJournal->getSetting('publicationFeeEnabled')}
	<tr>
		<td>{$currentJournal->getLocalizedSetting('publicationFeeName')|escape}</td>
	{if $publicationPayment}
		<td colspan="2">{translate key="payment.paid"} {$publicationPayment->getTimestamp()|date_format:$datetimeFormatLong}</td>
	{else}
		<td>{$currentJournal->getSetting('publicationFee')|string_format:"%.2f"} {$currentJournal->getSetting('currency')}</td>
		<td><a class="action" href="{url op="payPublicationFee" path=$submission->getId()}">{translate key="payment.payNow"}</a></td>
	{/if}
	</tr>	
{/if}
</table>
</div>
