{**
 * templates/user/subscriptions.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User subscriptions management page.
 *
 *}
{strip}
{assign var="pageTitle" value="user.subscriptions.mySubscriptions"}
{include file="frontend/components/header.tpl"}
{/strip}

{* Contact section *}
<div class="subscription_contact_section">
	{if $subscriptionAdditionalInformation}{$subscriptionAdditionalInformation|strip_unsafe_html}{/if}

	{if $subscriptionMailingAddress}
		<div class="address">
			{$subscriptionMailingAddress|nl2br|strip_unsafe_html}
		</div>
	{/if}

	{* Subscription contact *}
	{if $subscriptionName || $subscriptionPhone || $subscriptionEmail}
		<div class="contact primary">
			<h3>
				{translate key="about.contact.subscriptionContact"}
			</h3>

			{if $subscriptionName}
			<div class="name">
				{$subscriptionName|escape}
			</div>
			{/if}

			{if $subscriptionPhone}
			<div class="phone">
				<span class="label">
					{translate key="about.contact.phone"}
				</span>
				<span class="value">
					{$subscriptionPhone|escape}
				</span>
			</div>
			{/if}

			{if $subscriptionEmail}
			<div class="email">
				<a href="mailto:{$subscriptionEmail|escape}">
					{$subscriptionEmail|escape}
				</a>
			</div>
			{/if}
		</div>
	{/if}
</div>

{if $journalPaymentsEnabled}
<h3>{translate key="user.subscriptions.subscriptionStatus"}</h3>
<p>{translate key="user.subscriptions.statusInformation"}</p>
<table class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td>{translate key="user.subscriptions.status"}</td>
		<td>{translate key="user.subscriptions.statusDescription"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr>
		<td>{translate key="subscriptions.status.needsInformation"}</td>
		<td>{translate key="user.subscriptions.status.needsInformationDescription"}</td>
	</tr>
	<tr>
		<td>{translate key="subscriptions.status.needsApproval"}</td>
		<td>{translate key="user.subscriptions.status.needsApprovalDescription"}</td>
	</tr>
	<tr>
		<td>{translate key="subscriptions.status.awaitingManualPayment"}</td>
		<td>{translate key="user.subscriptions.status.awaitingManualPaymentDescription"}</td>
	</tr>
	<tr>
		<td>{translate key="subscriptions.status.awaitingOnlinePayment"}</td>
		<td>{translate key="user.subscriptions.status.awaitingOnlinePaymentDescription"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
</table>
{/if}

{if $individualSubscriptionTypesExist}
	<h3>{translate key="user.subscriptions.individualSubscriptions"}</h3>
	<p>{translate key="subscriptions.individualDescription"}</p>
	<table class="info">
	{if $userIndividualSubscription}
		<tr>
			<td>{$userIndividualSubscription->getLocalizedName()|escape}</td>
			<td>&nbsp;</td>
			<td>
			{assign var="subscriptionStatus" value=$userIndividualSubscription->getStatus()}
			{assign var="isNonExpiring" value=$userIndividualSubscription->isNonExpiring()}
			{if $journalPaymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
				<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span>
			{elseif $journalPaymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT}
				<span class="disabled">{translate key="subscriptions.status.awaitingManualPayment"}</span>
			{elseif $subscriptionStatus != $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
				<span class="disabled">{translate key="subscriptions.inactive"}</span>
			{else}
				{if $isNonExpiring}
					{translate key="subscriptionTypes.nonExpiring"}
				{else}
					{assign var="isExpired" value=$userIndividualSubscription->isExpired()}
					{if $isExpired}<span class="disabled">{translate key="user.subscriptions.expired"}: {$userIndividualSubscription->getDateEnd()|date_format:$dateFormatShort}</span>{else}{translate key="user.subscriptions.expires"}: {$userIndividualSubscription->getDateEnd()|date_format:$dateFormatShort}{/if}
				{/if}
			{/if}
			</td>
			<td align="right">
			{if $journalPaymentsEnabled}
				{if $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
					<a class="action" href="{url op="completePurchaseSubscription" path="individual"|to_array:$userIndividualSubscription->getId()}">{translate key="user.subscriptions.purchase"}</a>
				{elseif $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
					{if !$isNonExpiring}
						<a class="action" href="{url op="payRenewSubscription" path="individual"|to_array:$userIndividualSubscription->getId()}">{translate key="user.subscriptions.renew"}</a> |
					{/if}
					<a class="action" href="{url op="purchaseSubscription" path="individual"|to_array:$userIndividualSubscription->getId()}">{translate key="user.subscriptions.purchase"}</a>
				{/if}
			{else}
				&nbsp;
			{/if}
			</td>
		</tr>
	{elseif $journalPaymentsEnabled}
		<tr>
			<td colspan="3" align="left"><a class="action" href="{url op="purchaseSubscription" path="individual"}">{translate key="user.subscriptions.purchaseNewSubscription"}</a></td>
		</tr>
	{else}
		<tr>
			<td colspan="3" align="left"><a href="{url page="about" op="subscriptions" anchor="subscriptionTypes"}">{translate key="user.subscriptions.viewSubscriptionTypes"}</a></td>
		</tr>
	{/if}
	</table>
{/if}

{if $institutionalSubscriptionTypesExist}
	<h3>{translate key="user.subscriptions.institutionalSubscriptions"}</h3>
	<p>{translate key="subscriptions.institutionalDescription"}{if $journalPaymentsEnabled} {translate key="subscriptions.institutionalOnlinePaymentDescription"}{/if}</p>
	<table class="info">
	{if $userInstitutionalSubscriptions}
		{iterate from=userInstitutionalSubscriptions item=userInstitutionalSubscription}
		<tr>
			<td>{$userInstitutionalSubscription->getSubscriptionTypeName()|escape}</td>
			<td>{$userInstitutionalSubscription->getInstitutionName()|escape}</td>
			<td>
			{assign var="subscriptionStatus" value=$userInstitutionalSubscription->getStatus()}
			{assign var="isNonExpiring" value=$userInstitutionalSubscription->isNonExpiring()}
			{if $journalPaymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
				<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span>
			{elseif $journalPaymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_MANUAL_PAYMENT}
				<span class="disabled">{translate key="subscriptions.status.awaitingManualPayment"}</span>
			{elseif $journalPaymentsEnabled && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_NEEDS_APPROVAL}
				<span class="disabled">{translate key="subscriptions.status.needsApproval"}</span>
			{elseif $subscriptionStatus != $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
				<span class="disabled">{translate key="subscriptions.inactive"}</span>
			{else}
				{if $isNonExpiring}
					{translate key="subscriptionTypes.nonExpiring"}
				{else}
					{assign var="isExpired" value=$userInstitutionalSubscription->isExpired()}
					{if $isExpired}<span class="disabled">{translate key="user.subscriptions.expired"}: {$userInstitutionalSubscription->getDateEnd()|date_format:$dateFormatShort}</span>{else}{translate key="user.subscriptions.expires"}: {$userInstitutionalSubscription->getDateEnd()|date_format:$dateFormatShort}{/if}
				{/if}
			{/if}
			</td>
			<td align="right">
			{if $journalPaymentsEnabled}
				{if $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
					<a class="action" href="{url op="completePurchaseSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getId()}">{translate key="user.subscriptions.purchase"}</a>
				{elseif $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
					{if !$isNonExpiring}
						<a class="action" href="{url op="payRenewSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getId()}">{translate key="user.subscriptions.renew"}</a> |
					{/if}
					<a class="action" href="{url op="purchaseSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getId()}">{translate key="user.subscriptions.purchase"}</a>
				{/if}
			{else}
				&nbsp;
			{/if}
			</td>
		</tr>
		<tr><td class="separator" width="100%" colspan="4">&nbsp;</td></tr>
		{/iterate}
	{/if}
	{if $journalPaymentsEnabled}
		<tr>
			<td colspan="3" align="left"><a class="action" href="{url page="user" op="purchaseSubscription" path="institutional"}">{translate key="user.subscriptions.purchaseNewSubscription"}</a></td>
		</tr>
	{else}
		<tr>
			<td colspan="3" align="left"><a href="{url page="about" op="subscriptions" anchor="subscriptionTypes"}">{translate key="user.subscriptions.viewSubscriptionTypes"}</a></td>
		</tr>
	{/if}
	</table>
{/if}

{include file="frontend/components/footer.tpl"}
