{**
 * @file paymentForm.tpl
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for submitting a PayPal payment
 *
 *}
{assign var="pageTitle" value="plugins.paymethod.paypal"}
{include file="common/header.tpl"}
<table>
	<tr>
		<td><img src="{$baseUrl}/plugins/paymethod/paypal/images/paypal.gif" alt="paypal" /></td>
		<td>{$paypalDescription|escape}</td>
	</tr>
</table>

<p>{translate key="plugins.paymethod.paypal.warning"}</p>

<form action="{$paypalFormUrl}" id="paypalPaymentForm" name="paypalPaymentForm" method="post" style="margin-bottom: 0px;">
	{include file="common/formErrors.tpl"}
	{if $params.item_name}
	<table class="data" width="100%">
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.paypal.purchase.title"}</td>
			<td class="value" width="80%"><strong>{$params.item_name|escape}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.amount}
	<table class="data" width="100%">
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.paypal.purchase.fee"}</td>
			<td class="value" width="80%"><strong>{$params.amount|escape}{if $params.currency_code} ({$params.currency_code|escape}){/if}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.item_description}
	<table class="data" width="100%">
		<tr>
			<td class="label" colspan="2">{$params.item_description|escape|nl2br}</td>
		</tr>
	</table>
	{/if}	
	{foreach from=$params key="name" item="value"}
		<input type="hidden" name="{$name|escape}" value="{$value|escape}" />
	{/foreach}
	
	<p>{translate key="plugins.paymethod.paypal.warning"}</p>
	<p><input type="submit" name="submitBtn" value="{translate key="common.continue"}" class="button defaultButton" /></p>
</form>

{include file="common/footer.tpl"}
