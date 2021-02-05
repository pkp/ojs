{**
 * plugins/paymethod/manual/templates/paymentForm.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Manual payment page
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.paymethod.manual"}


<div class="page page_payment_form">
	<h1 class="page_title">
		{translate key="plugins.paymethod.manual"}
	</h1>

	<table class="cmp_table">
		<tr>
			<th>{translate key="plugins.paymethod.manual.purchase.title"}</th>
			<td>{$itemName|escape}</td>
		</tr>
		{if $itemAmount}
			<tr>
				<th>{translate key="plugins.paymethod.manual.purchase.fee"}</th>
				<td>{$itemAmount|string_format:"%.2f"}{if $itemCurrencyCode} ({$itemCurrencyCode|escape}){/if}</td>
			</tr>
		{/if}
	</table>

	<p>{$manualInstructions|nl2br}</p>

	<p>
		<a class="cmp_button" href="{url page="payment" op="plugin" path="ManualPayment"|to_array:"notify":$queuedPaymentId}" class="action">
			{translate key="plugins.paymethod.manual.sendNotificationOfPayment"}
		</a>
	</p>
</div>

{include file="frontend/components/footer.tpl"}
