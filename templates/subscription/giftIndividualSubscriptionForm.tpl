{**
 * templates/subscription/giftIndividualSubscriptionForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Gift purchase individual subscription form
 *}
{strip}
{assign var="pageTitle" value="gifts.purchaseGiftIndividualSubscription"}
{assign var="pageId" value="gifts.purchaseIndividualSubscriptionForm"}
{include file="common/header.tpl"}
{/strip}

<br/>

<form method="post" id="subscriptionGiftForm" action="{url op="payPurchaseGiftSubscription"}">

<p>{translate key="gifts.subscriptionFormIntroduction"}</p>

{include file="common/formErrors.tpl"}

<div id="giftBuyer">
<h3>{translate key="gifts.buyer"}</h3>
<p>{translate key="gifts.buyerDescription"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="buyerFirstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" id="buyerFirstName" name="buyerFirstName" value="{$buyerFirstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="buyerMiddleName" key="user.middleName"}</td>
	<td class="value"><input type="text" id="buyerMiddleName" name="buyerMiddleName" value="{$buyerMiddleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="buyerLastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" id="buyerLastName" name="buyerLastName" value="{$buyerLastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="buyerEmail" required="true" key="user.email"}</td>
	<td class="value"><input type="text" id="buyerEmail" name="buyerEmail" value="{$buyerEmail|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="confirmBuyerEmail" required="true" key="user.confirmEmail"}</td>
	<td class="value"><input type="text" id="confirmBuyerEmail" name="confirmBuyerEmail" value="{$confirmBuyerEmail|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
</table>
</div>

<div class="separator"></div>

<div id="giftRecipient">
<h3>{translate key="gifts.recipient"}</h3>
<p>{translate key="gifts.recipientDescription"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="recipientFirstName" required="true" key="user.firstName"}</td>
	<td width="80%" class="value"><input type="text" id="recipientFirstName" name="recipientFirstName" value="{$recipientFirstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="recipientMiddleName" key="user.middleName"}</td>
	<td class="value"><input type="text" id="recipientMiddleName" name="recipientMiddleName" value="{$recipientMiddleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="recipientLastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" id="recipientLastName" name="recipientLastName" value="{$recipientLastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="recipientEmail" required="true" key="user.email"}</td>
	<td class="value"><input type="text" id="recipientEmail" name="recipientEmail" value="{$recipientEmail|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="confirmRecipientEmail" required="true" key="user.confirmEmail"}</td>
	<td class="value"><input type="text" id="confirmRecipientEmail" name="confirmRecipientEmail" value="{$confirmRecipientEmail|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
</table>
</div>

<div class="separator"></div>

<div id="giftDetails">
<h3>{translate key="gifts.details"}</h3>
<p>{translate key="gifts.detailsDescription"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="typeId" required="true" key="gifts.gift"}</td>
	<td width="80%" class="value"><select name="typeId" id="typeId" class="selectMenu">
		{foreach from=$subscriptionTypes item=subscriptionType}
			<option value="{$subscriptionType->getTypeId()}"{if $typeId == $subscriptionType->getTypeId()} selected="selected"{/if}>{$subscriptionType->getSummaryString()|escape}</option>
		{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="giftLocale" required="true" key="common.language"}</td>
	<td class="value">
		<select name="giftLocale" id="giftLocale" class="selectMenu">
		{html_options options=$supportedLocales selected=$giftLocale|default:$formLocale}
		</select>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="giftNoteTitle" required="true" key="gifts.noteTitle"}</td>
	<td class="value"><input type="text" id="giftNoteTitle" name="giftNoteTitle" value="{$giftNoteTitle|escape}" size="60" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="giftNote" required="true" key="gifts.note"}</td>
	<td class="value"><textarea name="giftNote" id="giftNote" rows="5" cols="60" class="textArea">{$giftNote|escape}</textarea></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.continue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="index" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
