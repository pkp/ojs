{**
 * paymentSettingsForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit payment settings.
 *
 *}
{assign var="pageTitle" value="manager.payment.paymentMethods"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="payments"}">{translate key="manager.payment.options"}</a></li>
	<li class="current"><a href="{url op="payMethodSettings"}">{translate key="manager.payment.paymentMethods"}</a></li>
	<li><a href="{url op="viewPayments"}">{translate key="manager.payment.records"}</a></li>		
</ul>

{include file="common/formErrors.tpl"}

<script type="text/javascript">
<!--

{literal}
function changePaymentMethod() {
	document.paymentSettingsForm.action="{/literal}{url op="payMethodSettings" escape=false}{literal}";
		document.paymentSettingsForm.submit();
	}

	{/literal}
// -->
</script>

<form method="post" name="paymentSettingsForm" action="{url op="savePayMethodSettings"}">

<p>{translate key="manager.payment.paymentMethod.description"}</p>


<table width="100%" class="data">
	<tr valign="top">
		<td class="data" colspan="2">
			{assign var=pluginIndex value=1}
			<h4>{translate key="manager.payment.paymentMethods"}</h4>
			{foreach from=$paymentMethodPlugins item=plugin}
				&nbsp;<input type="radio" name="paymentMethodPluginName" id="paymentMethodPluginName-{$pluginIndex}" value="{$plugin->getName()|escape}" onClick="changePaymentMethod();" {if $paymentMethodPluginName == $plugin->getName()}checked="checked" {/if}/>&nbsp;<label for="paymentMethodPluginName-{$pluginIndex}">{$plugin->getDisplayName()|escape}</label><br/>
				<p>{$plugin->getDescription()}</p>
				{assign var=pluginIndex value=$pluginIndex+1}
			{/foreach}
		</td>
	{call_hook name="Template::Manager::Payment::displayPaymentSettingsForm" plugin=$paymentMethodPluginName}
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}