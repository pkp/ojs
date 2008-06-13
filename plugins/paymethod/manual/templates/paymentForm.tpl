{**
 * paymentForm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Manual payment page
 *
 *}
{assign var="pageTitle" value="plugins.paymethod.manual"}
{include file="common/header.tpl"}

<table class="data" width="100%">
	<tr>
		<td class="label" width="20%">{translate key="plugins.paymethod.manual.purchase.title"}</td>
		<td class="value" width="80%"><strong>{$itemName|escape}</strong></td>
	</tr>
	{if $itemAmount}
		<tr>
			<td class="label" width="20%">{translate key="plugins.paymethod.manual.purchase.fee"}</td>
			<td class="value" width="80%"><strong>{$itemAmount|string_format:"%.2f"}{if $itemCurrencyCode} ({$itemCurrencyCode|escape}){/if}</strong></td>
		</tr>
	{/if}
	{if $itemDescription}	
	<tr>
		<td colspan="2">{$itemDescription|escape|nl2br}</td>
	</tr>
	{/if}
</table>
<p>{$manualInstructions|nl2br}</p>

{include file="common/footer.tpl"}
