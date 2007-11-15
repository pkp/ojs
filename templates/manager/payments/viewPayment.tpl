{**
 * viewPayment.tpl
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Page to view one CompletedPayment in detail
 *
 *}
{assign var="pageTitle" value="common.payment"}
{include file="common/header.tpl"}

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
	<a name="payment"></a>
	<tr valign="top">
	<tr>
		<td width="25%">{translate key="manager.payment.paymentId"}</td>
		<td>{$payment->getCompletedPaymentId()}</td>
	</tr>
	<tr>
		<td width="25%">{translate key="user.username"}</td>
		<td><a class="action" href="{url op="userProfile" path=$payment->getUserId()}">{$payment->getUsername()|escape}</a></td>
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
	<tr><td colspan="2"><a class="action" href="{url page="subscriptionManager" op="editSubscription" path=$payment->getAssocId() }" >{translate key="manager.payment.editSubscription"}</a></td></tr>
	{/if}
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
</table>
<p><input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url path="payments" escape=false}'" /></p>
{include file="common/footer.tpl"}
