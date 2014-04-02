{**
 * templates/payments/paymentSettings.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for payment settings.
 *}
{strip}
{assign var="pageTitle" value="manager.payment.feePaymentOptions"}
{include file="common/header.tpl"}
{/strip}
<div id="paymentSettings">
<ul class="menu">
	<li class="current"><a href="{url op="payments"}">{translate key="manager.payment.options"}</a></li>
	<li><a href="{url op="payMethodSettings"}">{translate key="manager.payment.paymentMethods"}</a></li>
	<li><a href="{url op="viewPayments"}">{translate key="manager.payment.records"}</a></li>
</ul>

<br />

<form name="paymentSettingsForm" method="post" action="{url op="savePaymentSettings"}">
{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"paymentSettingsFormUrl" op="payments" escape=false}
			{form_language_chooser form="paymentSettingsForm" url=$paymentSettingsFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}

{include file="common/formErrors.tpl"}
<div id="generalOptions">
<h3>{translate key="manager.payment.generalOptions"}</h3>
<table width="100%" class="data">
	<tr>
		<td class="label" width="20%"><input type="checkbox" name="journalPaymentsEnabled" id="journalPaymentsEnabled" value="1"{if $journalPaymentsEnabled} checked="checked"{/if} /></td>
		<td class="value" width="80%">{fieldLabel name="journalPaymentsEnabled" key="manager.payment.options.enablePayments"}</td>
	</tr>
	<tr>
		<td class="label" width="20%">{fieldLabel name="currency" key="manager.payment.currency"}</td>
		<td class="value" width="80%"><select name="currency" id="currency" class="selectMenu">{html_options options=$validCurrencies selected=$currency}</select></td>
	</tr>
	<tr>
		<td width="20%"></td>
		<td width="80%">{translate key="manager.payment.currencymessage"}</td>
	</tr>
</table>
</div>
<div id="authorFees">
<h3>{translate key="manager.payment.authorFees"}</h3>
<p>{translate key="manager.payment.authorFeesDescription"}</p>
<table width="100%" class="data">
<tr>
	<td width="20%"><input type="checkbox" name="submissionFeeEnabled" id="submissionFeeEnabled" value="1"{if $submissionFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="submissionFeeEnabled" key="manager.payment.options.submissionFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="submissionFee" key="manager.payment.options.fee"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="submissionFee" id="submissionFee" size="10" value="{$submissionFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="submissionFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="submissionFeeName[{$formLocale|escape}]" id="submissionFeeName" size="50" value="{$submissionFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="submissionFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="submissionFeeDescription[{$formLocale|escape}]" id="submissionFeeDescription" rows="2" cols="50">{$submissionFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
<tr>
	<td width="20%"><input type="checkbox" name="fastTrackFeeEnabled" id="fastTrackFeeEnabled" value="1"{if $fastTrackFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="fastTrackFeeEnabled" key="manager.payment.options.fastTrackFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="fastTrackFee" key="manager.payment.options.fee"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="fastTrackFee" id="fastTrackFee" size="10" value="{$fastTrackFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="fastTrackFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="fastTrackFeeName[{$formLocale|escape}]" id="fastTrackFeeName" size="50" value="{$fastTrackFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="fastTrackFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="fastTrackFeeDescription[{$formLocale|escape}]" id="fastTrackFeeDescription" rows="2" cols="50">{$fastTrackFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
<tr>
	<td width="20%"><input type="checkbox" name="publicationFeeEnabled" id="publicationFeeEnabled" value="1"{if $publicationFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="publicationFeeEnabled" key="manager.payment.options.publicationFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="publicationFee" key="manager.payment.options.fee"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="publicationFee" id="publicationFee" size="10" value="{$publicationFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="publicationFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="publicationFeeName[{$formLocale|escape}]" id="publicationFeeName" size="50" value="{$publicationFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="publicationFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="publicationFeeDescription[{$formLocale|escape}]" id="publicationFeeDescription" rows="2" cols="50">{$publicationFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="waiverPolicy" key="manager.payment.options.waiverPolicy"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="waiverPolicy[{$formLocale|escape}]" id="waiverPolicy" rows="2" cols="50">{$waiverPolicy[$formLocale]|escape}</textarea></td>
</tr>
</table>
</div>
<div id="readerFees">
<h3>{translate key="manager.payment.readerFees"}</h3>

<p>{translate key="manager.payment.readerFeesDescription"}</p>

<table width="100%" class="data">
<tr>
	<td class="value" width="20%"><input type="checkbox" name="acceptSubscriptionPayments" id="acceptSubscriptionPayments" value="1"{if $acceptSubscriptionPayments} checked="checked"{/if} /></td>
	<td class="label" width="80%">{fieldLabel name="acceptSubscriptionPayments" key="manager.payment.options.acceptSubscriptionPayments"}</td>
</tr>
<tr>
	<td width="20%"><input type="checkbox" name="purchaseIssueFeeEnabled" id="purchaseIssueFeeEnabled" value="1"{if $purchaseIssueFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="purchaseIssueFeeEnabled" key="manager.payment.options.purchaseIssueFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="purchaseIssueFee" key="manager.payment.options.fee"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="purchaseIssueFee" id="purchaseIssueFee" size="10" value="{$purchaseIssueFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="purchaseIssueFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="purchaseIssueFeeName[{$formLocale|escape}]" id="purchaseIssueFeeName" size="50" value="{$purchaseIssueFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="purchaseIssueFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="purchaseIssueFeeDescription[{$formLocale|escape}]" id="purchaseIssueFeeDescription" rows="2" cols="50">{$purchaseIssueFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
<tr>
	<td width="20%"><input type="checkbox" name="purchaseArticleFeeEnabled" id="purchaseArticleFeeEnabled" value="1"{if $purchaseArticleFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="purchaseArticleFeeEnabled" key="manager.payment.options.purchaseArticleFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="purchaseArticleFee" key="manager.payment.options.fee"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="purchaseArticleFee" id="purchaseArticleFee" size="10" value="{$purchaseArticleFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="purchaseArticleFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="purchaseArticleFeeName[{$formLocale|escape}]" id="purchaseArticleFeeName" size="50" value="{$purchaseArticleFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="purchaseArticleFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="purchaseArticleFeeDescription[{$formLocale|escape}]" id="purchaseArticleFeeDescription" rows="2" cols="50">{$purchaseArticleFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
<tr>
	<td class="value" width="20%"><input type="checkbox" name="restrictOnlyPdf" id="restrictOnlyPdf" value="1"{if $restrictOnlyPdf} checked="checked"{/if} /></td>
	<td class="label" width="80%">{fieldLabel name="restrictOnlyPdf" key="manager.payment.options.onlypdf"}</td>
</tr>
</table>
</div>
<div id="generalFees">
<h3>{translate key="manager.payment.generalFees"}</h3>

<p>{translate key="manager.payment.generalFeesDescription"}</p>

<table width="100%" class="data">
<tr>
	<td width="20%"><input type="checkbox" name="membershipFeeEnabled" id="membershipFeeEnabled" value="1"{if $membershipFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="membershipFeeEnabled" key="manager.payment.options.membershipFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="membershipFee" key="manager.payment.options.fee"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="membershipFee" id="membershipFee" size="10" value="{$membershipFee|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="membershipFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="membershipFeeName[{$formLocale|escape}]" id="membershipFeeName" size="50" value="{$membershipFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="membershipFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="membershipFeeDescription[{$formLocale|escape}]" id="membershipFeeDescription" rows="2" cols="50">{$membershipFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
<tr>
	<td width="20%"><input type="checkbox" name="donationFeeEnabled" id="donationFeeEnabled" value="1"{if $donationFeeEnabled} checked="checked"{/if} /></td>
	<td width="80%">{fieldLabel name="donationFeeEnabled" key="manager.payment.options.donationFee"}</td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="donationFeeName" key="manager.payment.options.feeName"}</td>
	<td class="value" width="80%"><input type="text" class="textField" name="donationFeeName[{$formLocale|escape}]" id="donationFeeName" size="50" value="{$donationFeeName[$formLocale]|escape}" /></td>
</tr>
<tr>
	<td class="label" width="20%">{fieldLabel name="donationFeeDescription" key="manager.payment.options.feeDescription"}</td>
	<td class="value" width="80%"><textarea class="textArea" name="donationFeeDescription[{$formLocale|escape}]" id="donationFeeDescription" rows="2" cols="50">{$donationFeeDescription[$formLocale]|escape}</textarea></td>
</tr>
</table>
</div>
<div id="gifts">
<h3>{translate key="manager.payment.giftFees"}</h3>

<p>{translate key="manager.payment.giftFeesDescription"}</p>

<table width="100%" class="data">
<tr>
	<td class="value" width="20%"><input type="checkbox" name="acceptGiftSubscriptionPayments" id="acceptGiftSubscriptionPayments" value="1"{if $acceptGiftSubscriptionPayments} checked="checked"{/if} /></td>
	<td class="label" width="80%">{fieldLabel name="acceptGiftSubscriptionPayments" key="manager.payment.options.acceptGiftSubscriptionPayments"}</td>
</tr>
</table>
</div>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>
</form>
</div>
{include file="common/footer.tpl"}

