{**
 * templates/controllers/grid/settings/payment/form/paymentForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Section form under journal management.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#paymentForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="paymentForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.payments.SectionGridHandler" op="updateSection" paymentId=$paymentId}">
	{csrf}
	<input type="hidden" name="paymentId" value="{$paymentId|escape}"/>

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="paymentFormNotification"}

	{fbvFormArea id="statusArea"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="paid" checked=$paid label="manager.payments.paid"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
