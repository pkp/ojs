{**
 * viewPayments.tpl
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Table to view all past CompletedPayments
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="common.payments"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="payments"}">{translate key="manager.payment.options"}</a></li>
	<li><a href="{url op="payMethodSettings"}">{translate key="manager.payment.paymentMethods"}</a></li>
	<li class="current"><a href="{url op="viewPayments"}">{translate key="manager.payment.records"}</a></li>		
</ul>

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="25%">{translate key="common.user"}</td>
		<td width="25%">{translate key="manager.payment.paymentType"}</td>
		<td width="25%">{translate key="manager.payment.timestamp"}</td>
		<td width="25%">{translate key="manager.payment.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>

	{iterate from=payments item=payment}
	{assign var=isSubscription value=$payment->isSubscription()}
	{if $isSubscription}
		{assign var=subscriptionId value=$payment->getAssocId()}
		{if $individualSubscriptionDao->subscriptionExists($subscriptionId)}
			{assign var=isIndividual value=true}
		{elseif $institutionalSubscriptionDao->subscriptionExists($subscriptionId)}
			{assign var=isInstitutional value=true}
		{else}
			{assign var=isIndividual value=false}
			{assign var=isInstitutional value=false}
		{/if}
	{/if}
	<tr valign="top">
		<td>
			<a class="action" href="{url op="userProfile" path=$payment->getUserId()}">{$payment->getUsername()|escape|wordwrap:15:" ":true}</a>
		</td>
		<td>
			{if $isSubscription}
				{if $isIndividual}
					<a href="{url page="subscriptionManager" op="editSubscription" path="individual"|to_array:$subscriptionId}">{$payment->getName()|escape}</a>
				{elseif $isInstitutional}
					<a href="{url page="subscriptionManager" op="editSubscription" path="institutional"|to_array:$subscriptionId}">{$payment->getName()|escape}</a>
				{else}
					{$payment->getName()|escape}
				{/if}
			{else}
				{$payment->getName()|escape}
			{/if}
		</td>
		<td class="nowrap">
		{$payment->getTimestamp()|escape}
		</td>
		<td>
			<a href="{url op="viewPayment" path=$payment->getPaymentId()}" class="action">{translate key="manager.payment.details"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $payments->eof()}end{/if}separator">&nbsp;</td>
	</tr>
	{/iterate}
{if $payments->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.payment.noPayments"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$payments}</td>
		<td align="right">{page_links anchor="payments" name="payments" iterator=$payments}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
