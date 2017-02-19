{**
 * plugins/paymethod/dps/templates/paymentForm.tpl
 *
 * Robert Carter <r.carter@auckland.ac.nz>
 *
 * Based on the work of these people:
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for submitting a dps payment

 * appearing at a page similar to this
 * http://firstyears.dev.lbr.auckland.ac.nz/index.php/firstyears/user/payPurchaseSubscription/individual

 *}
{strip}
{assign var="pageTitle" value="plugins.paymethod.dps"}
{include file="common/header.tpl"}
{/strip}

<p><img src="{$baseUrl}/plugins/paymethod/dps/images/dps.png" alt="dps logo" /></p>
<p>{translate key="plugins.paymethod.dps.warning"}</p>
<form action="{$dpsFormPostUrl}" id="dpsPaymentForm" method="post" style="margin-bottom: 0px;">
	{include file="common/formErrors.tpl"}
	{if $params.item_name}
	<table class="data" width="100%">
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.dps.purchase.title"}</td>
			<td class="value" width="80%"><strong>{$params.item_name|escape}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.amount}
	<table class="data" width="100%">
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.dps.purchase.fee"}</td>
			<td class="value" width="80%"><strong>{$params.amount|string_format:"%.2f"}{if $params.currency_code} ({$params.currency_code|escape}){/if}</strong></td>
		</tr>
	</table>
	{/if}
	{if $params.item_description}
	<table class="data" width="100%">
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