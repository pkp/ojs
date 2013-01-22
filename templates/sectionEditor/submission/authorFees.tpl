{**
 * templates/sectionEditor/submission/authorFees.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display of author fees and payment information
 *
 *}
<div id="authorFees">
<h3>{translate key="manager.payment.authorFees"}</h3>
<table class="data">
{if $currentJournal->getSetting('submissionFeeEnabled')}
	<tr>
		<td>{$currentJournal->getLocalizedSetting('submissionFeeName')|escape}</td>
		<td>
	{if $submissionPayment}
		{translate key="payment.paid"} {$submissionPayment->getTimestamp()|date_format:$datetimeFormatLong}
	{else} 
		<a class="action" href="{url op="waiveSubmissionFee" path=$submission->getId() markAsPaid=true}">{translate key="payment.paymentReceived"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="waiveSubmissionFee" path=$submission->getId()}">{translate key="payment.waive"}</a>
	{/if}
		</td>
	</tr>
{/if}
{if $currentJournal->getSetting('fastTrackFeeEnabled')}
	<tr>
		<td>{$currentJournal->getLocalizedSetting('fastTrackFeeName')|escape}</td>
		<td> 
	{if $fastTrackPayment}
		{translate key="payment.paid"} {$fastTrackPayment->getTimestamp()|date_format:$datetimeFormatLong}
	{else}
		<a class="action" href="{url op="waiveFastTrackFee" path=$submission->getId() markAsPaid=true}">{translate key="payment.paymentReceived"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="waiveFastTrackFee" path=$submission->getId()}">{translate key="payment.waive"}</a>		
	{/if}
		</td>
	</tr>	
{/if}
{if $currentJournal->getSetting('publicationFeeEnabled')}
	<tr>
		<td>{$currentJournal->getLocalizedSetting('publicationFeeName')|escape}</td>
		<td>
	{if $publicationPayment}
		{translate key="payment.paid"} {$publicationPayment->getTimestamp()|date_format:$datetimeFormatLong}
	{else}
		<a class="action" href="{url op="waivePublicationFee" path=$submission->getId() markAsPaid=true}">{translate key="payment.paymentReceived"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="waivePublicationFee" path=$submission->getId()}">{translate key="payment.waive"}</a>		
	{/if}
		</td>
	</tr>
{/if}
</table>
</div>
