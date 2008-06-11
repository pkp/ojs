{**
 * block.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- subscription info.
 *
 *}
<div class="block" id="sidebarSubscription">
	<span class="blockTitle">{translate key="plugins.block.subscription.blockTitle"}</span>
	<strong>{$subscriptionTypeName|escape}</strong><br />
	{if $userHasSubscription}
		{if $subscriptionMembership}{$subscriptionMembership|escape}<br />{/if}
		{translate key="plugins.block.subscription.expires"}: {$subscriptionDateEnd|date_format:$dateFormatShort}<br />
		{if $journalPaymentsEnabled && $subscriptionEnabled && $userHasSubscription}
			<a href="{url page="user" op="payRenewSubscription"}">{translate key="payment.subscription.renew"}</a> 
		{/if}		
	{else}	
		{if $subscriptionMembership}
			{translate key="plugins.block.subscription.providedBy"}{$subscriptionMembership|escape}</br >
		{else}
			{translate key="plugins.block.subscription.providedByInstitution"}<br />
		{/if}
		{translate key="plugins.block.subscription.comingFromIP"}{$userIP|escape}<br />
	{/if}
</div>
