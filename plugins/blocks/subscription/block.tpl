{**
 * plugins/blocks/subscription/block.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- subscription info.
 *
 *}
<div class="block" id="sidebarSubscription">
	<span class="blockTitle">{translate key="plugins.block.subscription.blockTitle"}</span>
	{if $individualSubscription}
		{assign var=individualSubscriptionValid value=$individualSubscription->isValid()}
	{else}
		{assign var=individualSubscriptionValid value=false}
	{/if}
	{if $individualSubscription && $individualSubscriptionValid}
		{assign var=subscriptionStatus value=$individualSubscription->getStatus()}
		<strong>{$individualSubscription->getSubscriptionTypeName()|escape}</strong>
		{if $individualSubscription->getMembership()}({$individualSubscription->getMembership()|escape}){/if}<br />

		{if $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
			<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span><br />
		{elseif $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT}
			<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span><br />
		{else}
			{if $individualSubscription->isNonExpiring()}
				{translate key="subscriptionTypes.nonExpiring"}<br />
			{else}
				{if $individualSubscription->isExpired()}<span class="disabled">{translate key="user.subscriptions.expired"}: {else}{translate key="plugins.block.subscription.expires"}: {/if}{$individualSubscription->getDateEnd()|date_format:$dateFormatShort}<br />
			{/if}
		{/if}	
	{elseif $institutionalSubscription}
		{translate key="plugins.block.subscription.providedBy"}: <strong>{$institutionalSubscription->getInstitutionName()|escape}</strong><br />{translate key="plugins.block.subscription.comingFromIP"}: {$userIP|escape}<br />
	{elseif $individualSubscription && !$individualSubscriptionValid}
		{assign var=subscriptionStatus value=$individualSubscription->getStatus()}
		<strong>{$individualSubscription->getSubscriptionTypeName()|escape}</strong>
		{if $individualSubscription->getMembership()}({$individualSubscription->getMembership()|escape}){/if}<br />

		{if $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
			<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span><br />
		{elseif $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT}
			<span class="disabled">{translate key="subscriptions.status.awaitingManualPayment"}</span><br />
		{else}
			{if $individualSubscription->isNonExpiring()}
				{translate key="subscriptionTypes.nonExpiring"}<br />
			{else}
				{if $individualSubscription->isExpired()}<span class="disabled">{translate key="user.subscriptions.expired"}: {else}{translate key="plugins.block.subscription.expires"}: {/if}{$individualSubscription->getDateEnd()|date_format:$dateFormatShort}<br />
			{/if}
		{/if}		
	{elseif !$userLoggedIn}
		{translate key="plugins.block.subscription.loginToVerifySubscription"}
	{/if}
	{if $userLoggedIn}
		<a href="{url page="user" op="subscriptions"}">{translate key="user.subscriptions.mySubscriptions"}</a>
	{/if}
	{if $acceptGiftSubscriptionPayments}
		<br />
		<a href="{url page="gifts" op="purchaseGiftSubscription"}">{translate key="gifts.purchaseGiftSubscription"}</a>
	{/if}
</div>
