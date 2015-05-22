{**
 * templates/payments/viewPayment.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Page to view one CompletedPayment in detail
 *
 *}
{strip}
{assign var="pageTitle" value="common.payment"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="payments"}">{translate key="manager.payment.options"}</a></li>
	<li><a href="{url op="payMethodSettings"}">{translate key="manager.payment.paymentMethods"}</a></li>
	<li class="current"><a href="{url op="viewPayments"}">{translate key="manager.payment.records"}</a></li>
</ul>

<br />

{if $payment}
	<table width="100%" class="listing">
		<tr>
			<td colspan="4" class="headseparator">&nbsp;</td>
		</tr>
		<div id="payment">
		<tr valign="top">
		<tr>
			<td width="25%">{translate key="manager.payment.paymentId"}</td>
			<td>{$payment->getCompletedPaymentId()}</td>
		</tr>
		<tr>
			<td width="25%">{translate key="user.username"}</td>
			<td>
			{assign var=user value=$userDao->getById($payment->getUserId())}
			{if $isJournalManager}
				<a class="action" href="{url op="userProfile" path=$payment->getUserId()}">{$user->getUsername()|escape|wordwrap:15:" ":true}</a>
			{else}
				{$user->getUsername()|escape|wordwrap:15:" ":true}
			{/if}
			</td>
		</tr>
		<tr>
			<td width="25%">{translate key="manager.payment.description"}</td>
			<td>{$payment->getName()|escape}</td>
		</tr>
		<tr>
			<td width="25%">{translate key="manager.payment.timestamp"}</td>
			<td class="nowrap">
			{$payment->getTimestamp()|escape}
			</td>
		<tr>
		<tr>
			<td width="25%">{translate key="manager.payment.amount"}</td>
			<td>{$payment->getAmount()|string_format:"%.2f"} ({$payment->getCurrencyCode()|escape})</td>
		</tr>
		<tr>
			<td width="25%">{translate key="manager.payment.paymentMethod"}</td>
			<td>{$payment->getPayMethodPluginName()|escape}</td>
		</tr>
		<tr>
			<td colspan="2" class="separator">&nbsp;</td>
		</tr>
		<tr>
			<td width="25%">{translate key="manager.payment.details"}</td>
			<td>
				{if $payment->getAssocDescription()}
					({$payment->getAssocId()|escape}) {$payment->getAssocDescription()|escape}</td>
				{else}
					-
				{/if}
		</tr>
		{if $payment->isSubscription()}
			{assign var=subscriptionId value=$payment->getAssocId()}
			{if $individualSubscriptionDao->subscriptionExists($subscriptionId)}
				<tr>
					<td colspan="2">
						<a class="action" href="{url op="editSubscription" path="individual"|to_array:$subscriptionId}">{translate key="manager.payment.editSubscription"}</a>
					</td>
				</tr>
			{elseif $institutionalSubscriptionDao->subscriptionExists($subscriptionId)}
				<tr>
					<td colspan="2">
						<a class="action" href="{url op="editSubscription" path="institutional"|to_array:$subscriptionId}">{translate key="manager.payment.editSubscription"}</a>
					</td>
				</tr>
			{/if}
		{/if}
		<tr>
			<td colspan="2" class="endseparator">&nbsp;</td>
		</tr>
		</div>
	</table>
{else}
	{translate key="manager.payment.paymentId"} {translate key="manager.payment.notFound"}
{/if}
<p><input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" op="viewPayments" escape=false}'" /></p>
{include file="common/footer.tpl"}

