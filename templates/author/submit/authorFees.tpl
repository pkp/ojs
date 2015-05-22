{**
 * templates/author/submit/authorFees.tpl
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
<p>{translate key="about.authorFeesMessage"}</p>
{if $currentJournal->getSetting('submissionFeeEnabled')}
	<p>{$currentJournal->getLocalizedSetting('submissionFeeName')|escape}:
	{if $submissionPayment}
		{translate key="payment.paid"} {$submissionPayment->getTimestamp()|date_format:$datetimeFormatLong}
	{else}
		{$currentJournal->getSetting('submissionFee')|string_format:"%.2f"} ({$currentJournal->getSetting('currency')}) 
		{if $showPayLinks}<a class="action" href="{url op="paySubmissionFee" path=$articleId}">{translate key="payment.payNow"}</a>{/if}
	{/if}
	<br />{$currentJournal->getLocalizedSetting('submissionFeeDescription')|nl2br}</p>
{/if}
{if $currentJournal->getSetting('fastTrackFeeEnabled')}
	<p>{$currentJournal->getLocalizedSetting('fastTrackFeeName')|escape}: 
	{if $fastTrackPayment}
		{translate key="payment.paid"} {$fastTrackPayment->getTimestamp()|date_format:$datetimeFormatLong}
	{else}
		{$currentJournal->getSetting('fastTrackFee')|string_format:"%.2f"} ({$currentJournal->getSetting('currency')})
		{if $showPayLinks}<a class="action" href="{url op="payFastTrackFee" path=$articleId}">{translate key="payment.payNow"}</a>{/if}
	{/if}
	<br />{$currentJournal->getLocalizedSetting('fastTrackFeeDescription')|nl2br}</p>	
{/if}
{if $currentJournal->getSetting('publicationFeeEnabled')}
	<p>{$currentJournal->getLocalizedSetting('publicationFeeName')|escape}: {$currentJournal->getSetting('publicationFee')|string_format:"%.2f"} ({$currentJournal->getSetting('currency')})
	<br />{$currentJournal->getLocalizedSetting('publicationFeeDescription')|nl2br}</p>	
{/if}
{if $currentJournal->getLocalizedSetting('waiverPolicy') != ''}
	<p>{$currentJournal->getLocalizedSetting('waiverPolicy')|nl2br}</p>
{/if}
</div>
