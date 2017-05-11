{**
 * controllers/tab/settings/paymentMethod/form/paymentMethodForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Payment method management form.
 *
 *}

{* Help Link *}
{help file="settings.md" section="distribution" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#paymentMethodContainerDiv').pkpHandler(
			'$.pkp.controllers.tab.settings.paymentMethod.PaymentMethodHandler',
			{ldelim}
				paymentMethodFormUrlTemplate: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="getPaymentFormContents" paymentPluginName=PAYMENT_PLUGIN_NAME escape=false}
			{rdelim}
		);
		// Attach the container handler. (Triggers e.g. selectMonograph event.)
		$('#paymentMethodSelector').pkpHandler(
			'$.pkp.controllers.form.DropdownHandler',
			{ldelim}
				getOptionsUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="getPaymentMethods" escape=false},
				defaultKey: {$paymentPluginName|json_encode},
				eventName: 'selectPaymentMethod'
			{rdelim}
		);
		// Attach the AJAX form handler to the actual payment method config form.
		$('#paymentMethodForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<div id="paymentMethodContainerDiv">
	<form class="pkp_form" id="paymentMethodForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.DistributionSettingsTabHandler" op="saveFormData" tab="paymentMethod"}">
	{csrf}
	<input type="hidden" name="paymentPluginName" id="paymentPluginName" />
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="paymentMethodFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

		{fbvFormArea id="paymentMethod"}
			{fbvFormSection label="manager.paymentMethod.currency" description="manager.paymentMethod.currency.description"}
				{fbvElement required="true" type="select" id="currency" from=$currencies selected=$currency translate=false}
			{/fbvFormSection}
			<div id="paymentMethodSelector">
				{fbvFormSection label="manager.paymentMethod.method"}
					{fbvElement type="select" id="pluginSelect" from=$pluginNames translate=false}
				{/fbvFormSection}
			</div>
		{/fbvFormArea}

	{fbvFormArea id="paymentMethodFormContainer"}
		{* The form will be loaded into this container *}
	{/fbvFormArea}

	<div class="separator"></div>

	{if !$wizardMode}
		{fbvFormButtons id="paymentFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
	</form>
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
