{**
 * plugins/paymethod/paypal/templates/paymentForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for submitting a PayPal payment
 *}
{strip}
{assign var="pageTitle" value="plugins.paymethod.paypal"}
{include file="common/header.tpl"}
{/strip}

<p><img src="{$baseUrl}/plugins/paymethod/paypal/images/paypal_cards.png" alt="paypal" /></p>
<p>{translate key="plugins.paymethod.paypal.warning"}</p>
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#paypalPaymentForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" action="{$paypalFormUrl}" id="paypalPaymentForm" method="post" style="margin-bottom: 0px;">
	{csrf}
	{include file="common/formErrors.tpl"}
	{if $params.item_name}
	<table class="data">
		<tr>
			<td class="label">{translate key="plugins.paymethod.paypal.purchase.title"}</td>
			<td class="value"><strong>{$params.item_name|escape}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.amount}
	<table class="data">
		<tr>
			<td class="label">{translate key="plugins.paymethod.paypal.purchase.fee"}</td>
			<td class="value"><strong>{$params.amount|string_format:"%.2f"}{if $params.currency_code} ({$params.currency_code|escape}){/if}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.item_description}
	<table class="data">
		<tr>
			<td class="label" colspan="2">{$params.item_description|nl2br}</td>
		</tr>
	</table>
	{/if}
	{foreach from=$params key="name" item="value"}
		<input type="hidden" name="{$name|escape}" value="{$value|escape}" />
	{/foreach}

	<p><input type="submit" name="submitBtn" value="{translate key="common.continue"}" class="button defaultButton" /></p>
</form>
{include file="common/footer.tpl"}
