{**
 * paymentSettings.tpl
 *
 * Copyright (c) 2006 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for payment settings.
 *
 *}
{assign var="pageTitle" value="common.payments"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="payments"}">{translate key="manager.payment.settings"}</a></li>
	<li><a href="{url op="payMethodSettings"}">{translate key="manager.payment.paymentMethods"}</a></li>
	<li><a href="{url op="viewPayments"}">{translate key="manager.payments"}</a></li>		
</ul>

<br />

<form method="post" action="{url op="savePaymentSettings"}">

<table width="100%" class="listing">
	<tr>
		<td class="headseparator">&nbsp;</td>
	</tr>
	<tr>
		<td><input type="checkbox" name="journalPaymentsEnabled" id="journalPaymentsEnabled" value="1"{if $journalPaymentsEnabled} checked="checked"{/if} /> {translate key="manager.payment.settings.enablePayments"}</td>
	</tr>
	<tr>
		<td>{translate key="manager.payment.settings.notices"}</td>
	</tr>
	<tr>
		<td class="headseparator">&nbsp;</td>
	</tr>
</table>


{include file="common/formErrors.tpl"}
<table border="0" class="listing">
<tr>
	<td colspan="2" class="headseparator">&nbsp;</td>
</tr>
<tr class="heading" valign="bottom">
	<td width="70%">{translate key="manager.payment.description"}</td>
	<td width="30%">{translate key="manager.payment.cost"}</td>
</tr>
<tr>
	<td colspan="2" class="headseparator">&nbsp;</td>
</tr>

<tr>
	<td class="label" width="70%">{fieldLabel name="submissionFee" key="manager.payment.settings.submissionFee"}</td>
	<td class="value" width="30%"><input type="text" class="textField" name="submissionFee" id="submissionFee" size="10" value="{$submissionFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="fastTrackFee" key="manager.payment.settings.fastTrackFee"}</td>
	<td class="value" width="30%"><input type="text" class="textField" name="fastTrackFee" id="fastTrackFee" size="10" value="{$fastTrackFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="publicationFee" key="manager.payment.settings.publicationFee"}</td>
	<td class="value" width="30%"><input type="text" class="textField" name="publicationFee" id="publicationFee" size="10" value="{$publicationFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="membershipFee" key="manager.payment.settings.membershipFee"}</td>
	<td class="value" width="30%"><input type="text" class="textField" name="membershipFee" id="membershipFee" size="10" value="{$membershipFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="payPerViewFee" key="manager.payment.settings.payPerViewFee"}</td>
	<td class="value" width="30%"><input type="text" class="textField" name="payPerViewFee" id="payPerViewFee" size="10" value="{$payPerViewFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="restrictOnlyPdf" key="manager.payment.settings.onlypdf"}</td>
	<td class="value" width="30%"><input type="checkbox" name="restrictOnlyPdf" id="restrictOnlyPdf" value="1"{if $restrictOnlyPdf} checked="checked"{/if} /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="acceptSubscriptionPayments" key="manager.payment.settings.acceptSubscriptionPayments"}</td>
	<td class="value" width="30%"><input type="checkbox" name="acceptSubscriptionPayments" id="acceptSubscriptionPayments" value="1"{if $acceptSubscriptionPayments} checked="checked"{/if} /></td>
</tr>
<tr>
	<td class="label" width="70%">{fieldLabel name="acceptDonationPayments" key="manager.payment.settings.acceptDonationPayments"}</td>
	<td class="value" width="30%"><input type="checkbox" name="acceptDonationPayments" id="acceptDonationPayments" value="1"{if $acceptDonationPayments} checked="checked"{/if} /></td>	
</tr>
<tr>
	<td colspan="2" class="separator">&nbsp;</td>
</tr>
<tr><td colspan="2">{translate key="manager.payment.currencymessage"}</td></tr>
<tr>
	<td class="label">{fieldLabel name="currency" required="true" key="manager.payment.currency"}</td>
	<td><select name="currency" id="currency" class="selectMenu" />{html_options options=$validCurrencies selected=$currency}</select></td>
</tr>
<tr>
	<td colspan="2" class="endseparator">&nbsp;</td>
</tr>
</table>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>
</form>
<p>
<h4>{translate key="manager.payment.checksettings"}</h4>
{translate key="manager.payment.checksettings.explanation1"}<br />
{translate key="manager.payment.checksettings.enablesubscripitons"} ... 
	{if $enableSubscripitons}
		{translate key="manager.payment.checksettings.ok"}
	{else}
		<a href="{url page="manager" op="setup" path="4"}">{translate key="manager.payment.checksettings.notok"}</a>
	{/if}
</p>
<p>
{translate key="manager.payment.checksettings.explanation2"}
</p>
{include file="common/footer.tpl"}