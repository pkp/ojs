{**
 * plugins/paymethod/manual/templates/paymentForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Manual payment page
 *}
{strip}
{assign var="pageTitle" value="plugins.paymethod.manual"}
{include file="common/header.tpl"}
{/strip}

<div id="paymentForm">
<table class="data">
	<tr>
		<td class="label">{translate key="plugins.paymethod.manual.purchase.title"}</td>
		<td class="value"><strong>{$itemName|escape}</strong></td>
	</tr>
	{if $itemAmount}
		<tr>
			<td class="label">{translate key="plugins.paymethod.manual.purchase.fee"}</td>
			<td class="value"><strong>{$itemAmount|string_format:"%.2f"}{if $itemCurrencyCode} ({$itemCurrencyCode|escape}){/if}</strong></td>
		</tr>
	{/if}
	{if $itemDescription}
	<tr>
		<td colspan="2">{$itemDescription|nl2br}</td>
	</tr>
	{/if}
</table>
<p>{$manualInstructions|nl2br}</p>

<p><a href="{url page="payment" op="plugin" path="ManualPayment"|to_array:"notify":$queuedPaymentId|escape}" class="action">{translate key="plugins.paymethod.manual.sendNotificationOfPayment"}</a>
</div>
{include file="common/footer.tpl"}
